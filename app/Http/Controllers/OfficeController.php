<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Http\Requests\SaveOfficeRequest;
use App\Models\Office;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Non-teaching offices. Everyone in HR can read them; only the HR Head changes them.
 */
class OfficeController extends Controller
{
    public function index(): View
    {
        return view('offices.index', [
            'offices' => Office::query()->withCount(['employees', 'jobTitles'])->orderBy('name')->get(),
        ]);
    }

    public function store(SaveOfficeRequest $request, AuditLogger $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $audit): void {
            $office = Office::query()->create([...$request->validated(), 'is_active' => $request->boolean('is_active', true)]);
            $audit->log(AuditAction::Create, $office, ['code' => $office->code, 'name' => $office->name]);
        });

        return to_route('offices.index')->with('success', 'Office added.');
    }

    public function edit(Office $office): View
    {
        abort_unless(auth()->user()->isHrHead(), 403);

        return view('units.edit', [
            'unit' => $office,
            'heading' => 'Edit Office',
            'action' => route('offices.update', $office),
            'back' => route('offices.index'),
        ]);
    }

    public function update(SaveOfficeRequest $request, Office $office, AuditLogger $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $office, $audit): void {
            $office->fill([...$request->validated(), 'is_active' => $request->boolean('is_active')]);
            $changes = $audit->changesFor($office);
            $office->save();

            if ($changes !== []) {
                $audit->log(AuditAction::Update, $office, $changes);
            }
        });

        return to_route('offices.index')->with('success', 'Office updated.');
    }
}
