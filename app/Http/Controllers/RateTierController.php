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
 * The reference base rates by pay band and step. A change never edits an existing rate: it adds a new set of eight
 * rates from an effective date, so what was paid on any earlier date can still be looked up.
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
                ? RateTier::query()->where('salary_grade', $grade)->orderByDesc('effective_date')->orderBy('step')->get()->groupBy(fn (RateTier $tier) => $tier->effective_date->toDateString())
                : collect(),
        ]);
    }

    public function store(StoreRateScheduleRequest $request, AuditLogger $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $audit): void {
            $first = null;

            foreach (array_values($request->input('amounts')) as $index => $amount) {
                $tier = RateTier::query()->create([
                    'salary_grade' => $request->integer('salary_grade'),
                    'step' => $index + 1,
                    'monthly_amount' => $amount,
                    'effective_date' => $request->date('effective_date')->toDateString(),
                ]);
                $first ??= $tier;
            }

            $audit->log(AuditAction::Create, $first, [
                'salary_grade' => $request->integer('salary_grade'),
                'effective_date' => $request->date('effective_date')->toDateString(),
                'amounts' => array_map('floatval', array_values($request->input('amounts'))),
            ]);
        });

        return to_route('rates.index', ['grade' => $request->integer('salary_grade')])
            ->with('success', 'New rates saved. The earlier rates are kept.');
    }
}
