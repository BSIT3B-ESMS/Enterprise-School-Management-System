<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Http\Requests\StoreRateScheduleRequest;
use App\Models\JobTitle;
use App\Models\RateTier;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The reference base pay of each salary grade (Step 1 of the government schedule). A change never edits an existing
 * rate: it adds a new rate from an effective date, so what was paid on any earlier date can still be looked up.
 */
class RateTierController extends Controller
{
    public function index(Request $request): View
    {
        $schedule = RateTier::scheduleOn();
        $grade = $request->integer('grade') ?: null;

        return view('rates.index', [
            'schedule' => $schedule,
            'titlesByGrade' => JobTitle::query()->orderBy('title')->get()->groupBy('salary_grade'),
            'grade' => $grade && isset($schedule[$grade]) ? $grade : null,
            'history' => $grade
                ? RateTier::query()->where('salary_grade', $grade)->orderByDesc('effective_date')->get()
                : collect(),
        ]);
    }

    public function store(StoreRateScheduleRequest $request, AuditLogger $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $audit): void {
            $tier = RateTier::query()->create([
                'salary_grade' => $request->integer('salary_grade'),
                'monthly_amount' => $request->input('monthly_amount'),
                'effective_date' => $request->date('effective_date')->toDateString(),
            ]);

            $audit->log(AuditAction::Create, $tier, [
                'salary_grade' => $tier->salary_grade,
                'effective_date' => $tier->effective_date->toDateString(),
                'monthly_amount' => (float) $request->input('monthly_amount'),
            ]);
        });

        return to_route('rates.index', ['grade' => $request->integer('salary_grade')])
            ->with('success', 'The new rate was saved. The earlier rates are kept.');
    }
}
