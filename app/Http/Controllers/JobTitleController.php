<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Enums\JobTitleCategory;
use App\Http\Requests\SaveJobTitleRequest;
use App\Models\College;
use App\Models\JobTitle;
use App\Models\Office;
use App\Models\RateTier;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Job titles are shared: one title (for example Instructor I) is listed once and used in every college and office ticked for it. Its name,
 * category, salary grade, and active state are the same everywhere, so one edit changes them all. Each college or office still has its own
 * copy underneath, which holds its job postings and the people hired into it. Everyone in HR reads them; only the HR Admin changes them.
 */
class JobTitleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'unit' => ['nullable', 'regex:/^(college|office):\d+$/'],
            'category' => ['nullable', 'in:'.implode(',', array_column(JobTitleCategory::cases(), 'value'))],
        ]);

        $groups = JobTitle::query()
            ->selectRaw('title, category, min(id) as id, max(salary_grade) as salary_grade, count(*) as unit_count, sum(is_active) as active_count, sum(openings) as openings')
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where('title', 'like', "%{$term}%"))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when($filters['unit'] ?? null, function ($query, $unit): void {
                [$type, $id] = explode(':', $unit);
                $query->where($type === 'college' ? 'college_id' : 'office_id', (int) $id);
            })
            ->groupBy('title', 'category')
            ->orderBy('salary_grade')
            ->orderBy('title')
            ->paginate(20)
            ->withQueryString();

        $copies = JobTitle::query()
            ->with(['college', 'office'])
            ->withCount('employees')
            ->whereIn('title', $groups->pluck('title'))
            ->get()
            ->groupBy(fn (JobTitle $copy): string => $copy->title.'|'.$copy->category->value);

        return view('job-titles.index', [
            'jobTitles' => $groups,
            'copies' => $copies,
            'schedule' => RateTier::scheduleOn(),
            'unitOptions' => $this->unitOptions(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        abort_unless(auth()->user()->isHrAdmin(), 403);

        return view('job-titles.form', $this->formData(new JobTitle(['is_active' => true]), route('job-titles.store'), 'New Job Title', []));
    }

    public function store(SaveJobTitleRequest $request, AuditLogger $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $audit): void {
            foreach ($request->unitList() as $unit) {
                $jobTitle = JobTitle::query()->create($this->attributes($request) + ['college_id' => $unit['college_id'], 'office_id' => $unit['office_id']]);
                $audit->log(AuditAction::Create, $jobTitle, ['title' => $jobTitle->title, 'salary_grade' => $jobTitle->salary_grade]);
            }
        });

        return to_route('job-titles.index')->with('success', 'Job title added.');
    }

    public function edit(JobTitle $jobTitle): View
    {
        abort_unless(auth()->user()->isHrAdmin(), 403);

        $units = JobTitle::query()->where('title', $jobTitle->title)->where('category', $jobTitle->category)
            ->get()->map(fn (JobTitle $copy): string => $copy->college_id ? "college:{$copy->college_id}" : "office:{$copy->office_id}")->all();

        return view('job-titles.form', $this->formData($jobTitle, route('job-titles.update', $jobTitle), 'Edit Job Title', $units));
    }

    /**
     * Changes the job title everywhere it is used, and adds or removes the colleges and offices that use it. A college or office that
     * has people in this title cannot be removed.
     */
    public function update(SaveJobTitleRequest $request, JobTitle $jobTitle, AuditLogger $audit): RedirectResponse
    {
        $copies = JobTitle::query()->where('title', $jobTitle->title)->where('category', $jobTitle->category)->withCount(['employees', 'applications'])->get();
        $wanted = collect($request->unitList())->keyBy('key');

        $blocked = $copies->filter(fn (JobTitle $copy): bool => ! $wanted->has($this->unitKey($copy)) && ($copy->employees_count > 0 || $copy->applications_count > 0));

        if ($blocked->isNotEmpty()) {
            return back()->withInput()->withErrors(['units' => 'Keep '.$blocked->map(fn (JobTitle $copy): string => $copy->college?->name ?? $copy->office?->name)->implode(', ').': people or applications still use this job title there.']);
        }

        DB::transaction(function () use ($request, $copies, $wanted, $audit): void {
            foreach ($copies as $copy) {
                if (! $wanted->has($this->unitKey($copy))) {
                    $audit->log(AuditAction::Delete, $copy, ['title' => $copy->title, 'event' => 'unit_removed']);
                    $copy->delete();

                    continue;
                }

                $copy->fill($this->attributes($request));
                $changes = $audit->changesFor($copy);
                $copy->save();

                if ($changes !== []) {
                    $audit->log(AuditAction::Update, $copy, $changes);
                }
            }

            $existing = $copies->map(fn (JobTitle $copy): string => $this->unitKey($copy));

            foreach ($wanted->reject(fn (array $unit, string $key): bool => $existing->contains($key)) as $unit) {
                $added = JobTitle::query()->create($this->attributes($request) + ['college_id' => $unit['college_id'], 'office_id' => $unit['office_id']]);
                $audit->log(AuditAction::Create, $added, ['title' => $added->title, 'event' => 'unit_added']);
            }
        });

        return to_route('job-titles.index')->with('success', 'Job title updated everywhere it is used.');
    }

    private function unitKey(JobTitle $copy): string
    {
        return $copy->college_id ? "college:{$copy->college_id}" : "office:{$copy->office_id}";
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(SaveJobTitleRequest $request): array
    {
        return [
            'title' => $request->string('title')->trim()->toString(),
            'category' => $request->string('category')->toString(),
            'salary_grade' => $request->integer('salary_grade'),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function unitOptions(): array
    {
        return [
            'Colleges' => College::query()->orderBy('name')->get()->mapWithKeys(fn (College $college) => ["college:{$college->id}" => $college->name])->all(),
            'Offices' => Office::query()->orderBy('name')->get()->mapWithKeys(fn (Office $office) => ["office:{$office->id}" => $office->name])->all(),
        ];
    }

    /**
     * @param  list<string>  $units
     * @return array<string, mixed>
     */
    private function formData(JobTitle $jobTitle, string $action, string $heading, array $units): array
    {
        $schedule = RateTier::scheduleOn();

        return [
            'jobTitle' => $jobTitle,
            'action' => $action,
            'heading' => $heading,
            'unitOptions' => $this->unitOptions(),
            'selectedUnits' => collect(old('units', $units))->all(),
            'gradeOptions' => collect($schedule)->mapWithKeys(fn (string $amount, int $grade) => [
                $grade => 'SG '.$grade.' · ₱'.number_format((float) $amount).' a month',
            ])->all(),
            'categories' => collect(JobTitleCategory::cases())->mapWithKeys(fn (JobTitleCategory $category) => [
                $category->value => $category === JobTitleCategory::Faculty ? 'Faculty' : 'Non-teaching',
            ])->all(),
        ];
    }
}
