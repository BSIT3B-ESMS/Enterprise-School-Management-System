<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Http\Requests\SaveCollegeRequest;
use App\Models\College;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Colleges (the academic units). Everyone in HR can read them; only the HR Head changes them.
 */
class CollegeController extends Controller
{
    public function index(): View
    {
        return view('colleges.index', [
            'colleges' => College::query()
                ->withCount(['employees', 'programs'])
                ->with(['programs', 'deans' => fn ($deans) => $deans->current()->with('employee')])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(SaveCollegeRequest $request, AuditLogger $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $audit): void {
            $college = College::query()->create([...$request->validated(), 'is_active' => $request->boolean('is_active', true)]);
            $audit->log(AuditAction::Create, $college, ['code' => $college->code, 'name' => $college->name]);
        });

        return to_route('colleges.index')->with('success', 'College added.');
    }

    public function edit(College $college): View
    {
        abort_unless(auth()->user()->isHrHead(), 403);

        return view('units.edit', [
            'unit' => $college,
            'heading' => 'Edit College',
            'action' => route('colleges.update', $college),
            'back' => route('colleges.index'),
        ]);
    }

    public function update(SaveCollegeRequest $request, College $college, AuditLogger $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $college, $audit): void {
            $college->fill([...$request->validated(), 'is_active' => $request->boolean('is_active')]);
            $changes = $audit->changesFor($college);
            $college->save();

            if ($changes !== []) {
                $audit->log(AuditAction::Update, $college, $changes);
            }
        });

        return to_route('colleges.index')->with('success', 'College updated.');
    }
}
