<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The audit ledger, for the HR Admin: who did what and when, from the append-only audit_logs table. The values stored for
 * passwords and government IDs are never written there, so they cannot be shown. The report is a CSV of the current filters, and
 * generating it is itself logged.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage-reference-data');

        $filters = $this->filters($request);

        return view('audit.index', [
            'entries' => $this->query($filters)->paginate(20)->withQueryString(),
            'filters' => $filters,
            'actions' => AuditAction::cases(),
            'users' => User::query()->whereIn('id', AuditLog::query()->select('user_id')->whereNotNull('user_id')->distinct())->orderBy('name')->pluck('name', 'id'),
            'entities' => AuditLog::query()->whereNotNull('entity_type')->distinct()->orderBy('entity_type')->pluck('entity_type')->mapWithKeys(fn (string $type): array => [$type => ucfirst(strtolower(trim(preg_replace('/(?<!^)[A-Z]/', ' $0', class_basename($type)))))])->all(),
        ]);
    }

    public function report(Request $request, AuditLogger $audit): StreamedResponse
    {
        Gate::authorize('manage-reference-data');

        $filters = $this->filters($request);
        $audit->log(AuditAction::Export, null, ['report' => 'audit_logs', 'filters' => array_filter($filters)]);

        return response()->streamDownload(function () use ($filters): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['When', 'Who', 'Action', 'Record', 'What happened', 'IP address']);

            $this->query($filters)->chunk(500, function ($entries) use ($out): void {
                foreach ($entries as $entry) {
                    fputcsv($out, [$entry->created_at->format('Y-m-d H:i:s'), $entry->user?->name ?? 'System', $entry->action->value, $entry->entity_type ? class_basename($entry->entity_type).' #'.$entry->entity_id : '', $entry->summary(), $entry->ip_address]);
                }
            });

            fclose($out);
        }, 'audit-logs-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return $request->validate([
            'action' => ['nullable', 'in:'.implode(',', array_column(AuditAction::cases(), 'value'))],
            'user' => ['nullable', 'integer'],
            'entity' => ['nullable', 'string', 'max:60'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<AuditLog>
     */
    private function query(array $filters): Builder
    {
        return AuditLog::query()
            ->with('user')
            ->when($filters['action'] ?? null, fn ($query, $action) => $query->where('action', $action))
            ->when($filters['user'] ?? null, fn ($query, $user) => $query->where('user_id', $user))
            ->when($filters['entity'] ?? null, fn ($query, $entity) => $query->where('entity_type', $entity))
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest('id');
    }
}
