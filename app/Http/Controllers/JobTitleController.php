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
 * Job titles (positions) of each college and office, with the pay band that sets their base rate. Everyone in HR reads
 * them; only the HR Head changes them.
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

        $jobTitles = JobTitle::query()
            ->with(['college', 'office'])
            ->withCount('employees')
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where('title', 'like', "%{$term}%"))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when($filters['unit'] ?? null, function ($query, $unit): void {
                [$type, $id] = explode(':', $unit);
                $query->where($type === 'college' ? 'college_id' : 'office_id', (int) $id);
            })
            ->orderBy('salary_grade')
            ->orderBy('title')
            ->paginate(20)
            ->withQueryString();

        return view('job-titles.index', [
            'jobTitles' => $jobTitles,
            'schedule' => RateTier::scheduleOn(),
            'unitOptions' => $this->unitOptions(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        abort_unless(auth()->user()->isHrHead(), 403);

        return view('job-titles.form', $this->formData(new JobTitle(['is_active' => true]), route('job-titles.store'), 'New Job Title'));
    }

    public function store(SaveJobTitleRequest $request, AuditLogger $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $audit): void {
            $jobTitle = JobTitle::query()->create($this->attributes($request));
            $audit->log(AuditAction::Create, $jobTitle, ['title' => $jobTitle->title, 'salary_grade' => $jobTitle->salary_grade]);
        });

        return to_route('job-titles.index')->with('success', 'Job title added.');
    }

    public function edit(JobTitle $jobTitle): View
    {
        abort_unless(auth()->user()->isHrHead(), 403);

        return view('job-titles.form', $this->formData($jobTitle, route('job-titles.update', $jobTitle), 'Edit Job Title'));
    }

    public function update(SaveJobTitleRequest $request, JobTitle $jobTitle, AuditLogger $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $jobTitle, $audit): void {
            $jobTitle->fill($this->attributes($request));
            $changes = $audit->changesFor($jobTitle);
            $jobTitle->save();

            if ($changes !== []) {
                $audit->log(AuditAction::Update, $jobTitle, $changes);
            }
        });

        return to_route('job-titles.index')->with('success', 'Job title updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(SaveJobTitleRequest $request): array
    {
        return [
            'title' => $request->string('title')->trim()->toString(),
            'category' => $request->string('category')->toString(),
            ...$request->unitIds(),
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
     * @return array<string, mixed>
     */
    private function formData(JobTitle $jobTitle, string $action, string $heading): array
    {
        $schedule = RateTier::scheduleOn();

        return [
            'jobTitle' => $jobTitle,
            'action' => $action,
            'heading' => $heading,
            'unitOptions' => $this->unitOptions(),
            'unitValue' => $jobTitle->college_id ? "college:{$jobTitle->college_id}" : ($jobTitle->office_id ? "office:{$jobTitle->office_id}" : null),
            'bandOptions' => collect($schedule)->mapWithKeys(fn (array $steps, int $grade) => [
                $grade => 'Pay band '.$grade.' · ₱'.number_format((float) reset($steps)).' to ₱'.number_format((float) end($steps)),
            ])->all(),
            'categories' => collect(JobTitleCategory::cases())->mapWithKeys(fn (JobTitleCategory $category) => [
                $category->value => $category === JobTitleCategory::Faculty ? 'Faculty' : 'Non-teaching',
            ])->all(),
        ];
    }
}
