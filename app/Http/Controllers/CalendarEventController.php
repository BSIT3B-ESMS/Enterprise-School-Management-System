<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Models\CalendarEvent;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * HR calendar events that are not interviews (meetings, trainings, deadlines, holidays): add, edit, move to another day with a
 * reason, and cancel. An event marked for everyone also shows on the Employee calendar.
 */
class CalendarEventController extends Controller
{
    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data, $request, $audit): void {
            $event = CalendarEvent::query()->create($this->attributes($data, $request) + ['created_by' => $request->user()->id, 'status' => 'scheduled']);
            $audit->log(AuditAction::Create, $event, ['title' => $event->title, 'starts_at' => $event->starts_at->toDateTimeString()]);
        });

        return back()->with('success', 'The event was added.');
    }

    public function update(Request $request, CalendarEvent $event, AuditLogger $audit): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($event, $data, $request, $audit): void {
            $event->fill($this->attributes($data, $request));
            $changes = $audit->changesFor($event);
            $event->save();
            $audit->log(AuditAction::Update, $event, $changes);
        });

        return back()->with('success', 'The event was saved.');
    }

    /**
     * Moves an event to another day (never before today) with a reason; the times stay unless new ones are given.
     */
    public function move(Request $request, CalendarEvent $event, AuditLogger $audit): RedirectResponse
    {
        abort_if($event->status !== 'scheduled', 422, 'Only a scheduled event can be moved.');

        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'reason' => ['required', 'string', 'max:500'],
        ], ['reason.required' => 'Give a reason for moving the event.']);

        DB::transaction(function () use ($event, $data, $audit): void {
            $length = $event->ends_at ? $event->starts_at->diffInMinutes($event->ends_at) : null;
            $start = Carbon::parse($data['date'].' '.($data['start_time'] ?? $event->starts_at->format('H:i')));
            $old = $event->starts_at->toDateTimeString();

            $event->forceFill([
                'starts_at' => $start,
                'ends_at' => isset($data['end_time']) ? Carbon::parse($data['date'].' '.$data['end_time']) : ($length !== null ? $start->copy()->addMinutes((int) $length) : null),
            ])->save();

            $audit->log(AuditAction::Update, $event, ['event' => 'event_moved', 'from' => $old, 'to' => $start->toDateTimeString(), 'reason' => $data['reason']]);
        });

        return back()->with('success', 'The event was moved.');
    }

    public function cancel(Request $request, CalendarEvent $event, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']], ['reason.required' => 'Give a reason for cancelling the event.']);

        DB::transaction(function () use ($event, $data, $audit): void {
            $event->forceFill(['status' => 'cancelled'])->save();
            $audit->log(AuditAction::Update, $event, ['event' => 'event_cancelled', 'reason' => $data['reason']]);
        });

        return back()->with('success', 'The event was cancelled.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:'.implode(',', array_keys(CalendarEvent::TYPES))],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'venue' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data, Request $request): array
    {
        return [
            'title' => $data['title'],
            'type' => $data['type'],
            'starts_at' => Carbon::parse($data['date'].' '.$data['start_time']),
            'ends_at' => filled($data['end_time'] ?? null) ? Carbon::parse($data['date'].' '.$data['end_time']) : null,
            'venue' => $data['venue'] ?? null,
            'notes' => $data['notes'] ?? null,
            'for_everyone' => $request->boolean('for_everyone'),
        ];
    }
}
