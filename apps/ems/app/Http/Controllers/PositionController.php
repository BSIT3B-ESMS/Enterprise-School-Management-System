<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\College;
use App\Models\JobTitle;
use App\Models\RateTier;
use App\Services\AcademicCalendar;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * The open positions, shown on the public Careers website (anyone) and inside the Applicant interface (a signed-in applicant).
 * Only published, active positions with at least one opening are listed. The pay shown is the base rate (Step 1) of the salary
 * grade. Benefits are not shown: HR chooses them in the offer, after the interview.
 */
class PositionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'in:full_time,part_time'],
            'college' => ['nullable', 'integer'],
        ]);

        $open = JobTitle::query()->openForApplications()->with(['college', 'office'])->orderBy('title')->get();

        $positions = $open
            ->when($filters['q'] ?? null, fn ($rows, string $term) => $rows->filter(fn (JobTitle $position): bool => str_contains(mb_strtolower("{$position->title} {$position->college?->name} {$position->office?->name} {$position->location} {$position->summary}"), mb_strtolower($term))))
            ->when($filters['type'] ?? null, fn ($rows, string $type) => $rows->where('employment_type', $type))
            ->when($filters['college'] ?? null, fn ($rows, $college) => $rows->where('college_id', (int) $college))
            ->values();

        return view($this->isPublic($request) ? 'careers.index' : 'applicant.positions', [
            'positions' => $positions,
            'featured' => $open->sortByDesc('openings')->take(3)->values(),
            'stats' => [
                'positions' => $open->count(),
                'colleges' => $open->pluck('college_id')->filter()->unique()->count(),
                'openings' => (int) $open->sum('openings'),
            ],
            'colleges' => College::query()->whereIn('id', $open->pluck('college_id')->filter())->orderBy('name')->pluck('name', 'id'),
            'filters' => $filters,
            'pay' => RateTier::scheduleOn(),
            'appliedIds' => $this->appliedIds($request),
            'context' => $this->isPublic($request) ? 'public' : 'applicant',
        ]);
    }

    public function show(Request $request, int $position, AcademicCalendar $calendar): View
    {
        $job = JobTitle::query()->with(['college', 'office'])->where('is_published', true)->where('is_active', true)->findOrFail($position);

        return view($this->isPublic($request) ? 'careers.show' : 'applicant.position', [
            'position' => $job,
            'pay' => RateTier::scheduleOn()[$job->salary_grade] ?? null,
            'applied' => $this->appliedIds($request)->contains($job->id),
            'existingApplication' => $request->user()?->isApplicant()
                ? Application::query()->where('user_id', $request->user()->id)->where('job_title_id', $job->id)->latest('id')->first()
                : null,
            'termLabel' => $job->contract_term && $job->academic_year ? $job->contract_term->label().', AY '.$calendar->yearLabel($job->academic_year) : null,
            'context' => $this->isPublic($request) ? 'public' : 'applicant',
        ]);
    }

    private function isPublic(Request $request): bool
    {
        return $request->routeIs('careers.*');
    }

    /**
     * @return Collection<int, int>
     */
    private function appliedIds(Request $request)
    {
        $user = $request->user();

        if (! $user?->isApplicant()) {
            return collect();
        }

        return Application::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [ApplicationStatus::Applied, ApplicationStatus::DemoInterview, ApplicationStatus::Offered, ApplicationStatus::Hired])
            ->pluck('job_title_id');
    }
}
