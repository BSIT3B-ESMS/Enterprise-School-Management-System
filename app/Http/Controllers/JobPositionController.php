<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Enums\ContractTerm;
use App\Enums\EmploymentType;
use App\Models\College;
use App\Models\JobTitle;
use App\Models\Office;
use App\Models\RateTier;
use App\Services\AcademicCalendar;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Open Job Positions: the job postings. A posting is a job title that HR has opened (it has openings and is published); only open
 * postings appear on the Careers website. Everyone in HR can read them; the HR Admin opens, edits, and closes them. A full-time
 * posting has no end of contract; a part-time posting says which semester or academic year it runs. Benefits are not part of a posting.
 */
class JobPositionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'college' => ['nullable', 'integer'],
            'office' => ['nullable', 'integer'],
            'type' => ['nullable', 'in:full_time,part_time'],
            'status' => ['nullable', 'in:open,closed'],
        ]);

        $postings = JobTitle::query()
            ->with(['college', 'office'])
            ->withCount(['applications as open_applications_count' => fn ($query) => $query->open()])
            ->where(fn ($query) => $query->where('openings', '>', 0)->orWhere('is_published', true)->orWhereNotNull('summary'))
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where('title', 'like', "%{$term}%"))
            ->when($filters['college'] ?? null, fn ($query, $college) => $query->where('college_id', $college))
            ->when($filters['office'] ?? null, fn ($query, $office) => $query->where('office_id', $office))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('employment_type', $type))
            ->when(($filters['status'] ?? null) === 'open', fn ($query) => $query->openForApplications())
            ->when(($filters['status'] ?? null) === 'closed', fn ($query) => $query->where(fn ($closed) => $closed->where('openings', 0)->orWhere('is_published', false)->orWhere('is_active', false)))
            ->orderBy('title')
            ->paginate(15)
            ->withQueryString();

        return view('job-positions.index', [
            'postings' => $postings,
            'filters' => $filters,
            'pay' => RateTier::scheduleOn(),
            'colleges' => College::query()->orderBy('name')->pluck('name', 'id'),
            'offices' => Office::query()->orderBy('name')->pluck('name', 'id'),
            'unopened' => JobTitle::query()->with(['college', 'office'])->where('is_active', true)->where('openings', 0)->where('is_published', false)->whereNull('summary')->orderBy('title')->get(),
            'canManage' => Gate::allows('manage-reference-data'),
        ]);
    }

    public function open(Request $request): RedirectResponse
    {
        Gate::authorize('manage-reference-data');

        $title = JobTitle::query()->where('is_active', true)->findOrFail($request->integer('job_title'));

        return to_route('job-positions.edit', $title);
    }

    public function edit(JobTitle $jobTitle, AcademicCalendar $calendar): View
    {
        Gate::authorize('manage-reference-data');

        return view('job-positions.form', [
            'jobTitle' => $jobTitle->load(['college', 'office']),
            'terms' => collect(ContractTerm::cases())->mapWithKeys(fn (ContractTerm $term): array => [$term->value => $term->label()])->all(),
            'academicYears' => $calendar->selectableYears(),
            'pay' => RateTier::scheduleOn()[$jobTitle->salary_grade] ?? null,
        ]);
    }

    public function update(Request $request, JobTitle $jobTitle, AuditLogger $audit, AcademicCalendar $calendar): RedirectResponse
    {
        Gate::authorize('manage-reference-data');

        $data = $request->validate([
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'contract_term' => ['nullable', Rule::enum(ContractTerm::class)],
            'academic_year' => ['nullable', 'integer', 'between:2000,2100'],
            'location' => ['required', 'string', 'max:150'],
            'openings' => ['required', 'integer', 'between:0,99'],
            'is_published' => ['sometimes', 'boolean'],
            'summary' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:5000'],
            'responsibilities' => ['required', 'string', 'max:3000'],
            'qualifications' => ['required', 'string', 'max:3000'],
            'requirements' => ['nullable', 'string', 'max:3000'],
        ], [
            'summary.max' => 'The short description is limited to 200 characters.',
        ]);

        $isPartTime = $data['employment_type'] === EmploymentType::PartTime->value;

        if ($isPartTime && (blank($data['contract_term'] ?? null) || blank($data['academic_year'] ?? null))) {
            return back()->withInput()->withErrors(['contract_term' => 'A part-time post needs its end of contract: choose the semester or whole academic year, and the academic year.']);
        }

        $lines = fn (?string $text): array => collect(preg_split('/\R/', (string) $text))->map(fn ($line) => trim($line))->filter()->values()->all();

        DB::transaction(function () use ($jobTitle, $data, $isPartTime, $lines, $request, $audit): void {
            $jobTitle->fill([
                'employment_type' => $data['employment_type'],
                'contract_term' => $isPartTime ? $data['contract_term'] : null,
                'academic_year' => $isPartTime ? $data['academic_year'] : null,
                'location' => $data['location'],
                'openings' => $data['openings'],
                'is_published' => $request->boolean('is_published'),
                'summary' => $data['summary'],
                'description' => $data['description'],
                'responsibilities' => $lines($data['responsibilities']),
                'qualifications' => $lines($data['qualifications']),
                'requirements' => $lines($data['requirements'] ?? null),
            ]);
            $changes = $audit->changesFor($jobTitle);
            $jobTitle->save();

            if ($changes !== []) {
                $audit->log(AuditAction::Update, $jobTitle, ['event' => 'posting_saved', ...$changes]);
            }
        });

        return to_route('job-positions.index')->with('success', "{$jobTitle->title} was saved.".($jobTitle->isOpen() ? ' It is listed on the Careers website.' : ' It is closed, so it is not listed on the Careers website.'));
    }

    /**
     * Closes a posting: no openings, not published. The job title and its employees stay; applications already filed continue.
     */
    public function close(JobTitle $jobTitle, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('manage-reference-data');

        DB::transaction(function () use ($jobTitle, $audit): void {
            $jobTitle->forceFill(['openings' => 0, 'is_published' => false]);
            $changes = $audit->changesFor($jobTitle);
            $jobTitle->save();
            $audit->log(AuditAction::Update, $jobTitle, ['event' => 'posting_closed', ...$changes]);
        });

        return to_route('job-positions.index')->with('success', "{$jobTitle->title} is closed.");
    }
}
