<?php

use App\Enums\AuditAction;
use App\Enums\JobTitleCategory;
use App\Models\AuditLog;
use App\Models\College;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Office;
use App\Models\RateTier;

function seedPayBand(int $grade, float $first = 30000, float $last = 37000, string $date = '2026-01-01'): void
{
    foreach (range(1, 8) as $step) {
        RateTier::factory()->create([
            'salary_grade' => $grade,
            'step' => $step,
            'monthly_amount' => $first + ($last - $first) * ($step - 1) / 7,
            'effective_date' => $date,
        ]);
    }
}

function jobTitleInput(array $overrides = []): array
{
    return [
        'title' => 'Lecturer',
        'category' => 'faculty',
        'unit' => 'college:'.College::factory()->create()->id,
        'salary_grade' => 5,
        'is_active' => '1',
        ...$overrides,
    ];
}

beforeEach(fn () => seedPayBand(5));

describe('the list', function () {
    it('shows each title with its college or office and its base pay from step 1 to step 8', function () {
        $college = College::factory()->create(['name' => 'College of Nursing']);
        JobTitle::factory()->create(['title' => 'Instructor I', 'college_id' => $college->id, 'salary_grade' => 5]);

        $this->actingAs(hrStaff())->get(route('job-titles.index'))
            ->assertOk()
            ->assertSee('Instructor I')
            ->assertSee('College of Nursing')
            ->assertSee('₱30,000 to ₱37,000');
    });

    it('filters by search term, college or office, and category', function () {
        $college = College::factory()->create();
        $office = Office::factory()->create();
        JobTitle::factory()->create(['title' => 'Dean of Nursing', 'college_id' => $college->id]);
        JobTitle::factory()->nonTeaching()->create(['title' => 'Records Clerk', 'office_id' => $office->id]);

        $this->actingAs(hrHead());

        $this->get(route('job-titles.index', ['q' => 'Nursing']))->assertSee('Dean of Nursing')->assertDontSee('Records Clerk');
        $this->get(route('job-titles.index', ['unit' => "office:{$office->id}"]))->assertSee('Records Clerk')->assertDontSee('Dean of Nursing');
        $this->get(route('job-titles.index', ['category' => 'non_teaching']))->assertSee('Records Clerk')->assertDontSee('Dean of Nursing');
    });

    it('counts the people holding each title', function () {
        $title = JobTitle::factory()->create(['title' => 'Lecturer']);
        Employee::factory()->count(3)->create(['job_title_id' => $title->id]);

        $this->actingAs(hrHead())->get(route('job-titles.index'))->assertSeeInOrder(['Lecturer', '3']);
    });

    it('shows the add and edit controls to the HR Head only', function () {
        $title = JobTitle::factory()->create();

        $this->actingAs(hrHead())->get(route('job-titles.index'))
            ->assertSee('Add Job Title')
            ->assertSee(route('job-titles.edit', $title), escape: false);

        $this->actingAs(hrStaff())->get(route('job-titles.index'))
            ->assertDontSee('Add Job Title')
            ->assertDontSee(route('job-titles.edit', $title), escape: false);
    });
});

describe('adding a job title', function () {
    it('saves it to the chosen college and records it in the audit log', function () {
        $college = College::factory()->create();

        $this->actingAs(hrHead())->post(route('job-titles.store'), jobTitleInput(['unit' => "college:{$college->id}"]))
            ->assertRedirect(route('job-titles.index'));

        $title = JobTitle::query()->where('title', 'Lecturer')->sole();

        expect($title->college_id)->toBe($college->id)
            ->and($title->office_id)->toBeNull()
            ->and($title->category)->toBe(JobTitleCategory::Faculty)
            ->and(AuditLog::query()->where('action', AuditAction::Create)->where('entity_id', $title->id)->exists())->toBeTrue();
    });

    it('saves a title that belongs to an office', function () {
        $office = Office::factory()->create();

        $this->actingAs(hrHead())->post(route('job-titles.store'), jobTitleInput(['unit' => "office:{$office->id}", 'category' => 'non_teaching']));

        $title = JobTitle::query()->where('title', 'Lecturer')->sole();

        expect($title->office_id)->toBe($office->id)->and($title->college_id)->toBeNull();
    });

    it('rejects :scenario', function (array $override, string $field) {
        $this->actingAs(hrHead())->post(route('job-titles.store'), jobTitleInput($override))->assertSessionHasErrors($field);

        expect(JobTitle::query()->count())->toBe(0);
    })->with([
        'a missing title' => [['title' => ''], 'title'],
        'an unknown category' => [['category' => 'janitor'], 'category'],
        'a malformed college or office' => [['unit' => 'everywhere'], 'unit'],
        'a college that does not exist' => [['unit' => 'college:999999'], 'unit'],
        'a pay band that does not exist' => [['salary_grade' => 77], 'salary_grade'],
    ]);

    it('rejects a name already used in the same college but allows it in another', function () {
        $college = College::factory()->create();
        JobTitle::factory()->create(['title' => 'Lecturer', 'college_id' => $college->id]);

        $this->actingAs(hrHead())->post(route('job-titles.store'), jobTitleInput(['unit' => "college:{$college->id}"]))
            ->assertSessionHasErrors('title');

        $this->post(route('job-titles.store'), jobTitleInput())->assertSessionHasNoErrors();
    });

    it('is forbidden to HR Staff', function () {
        $this->actingAs(hrStaff())->get(route('job-titles.create'))->assertForbidden();
        $this->post(route('job-titles.store'), jobTitleInput())->assertForbidden();

        expect(JobTitle::query()->count())->toBe(0);
    });
});

describe('editing a job title', function () {
    it('changes the pay band and records the old and new value', function () {
        seedPayBand(6, 40000, 47000);
        $title = JobTitle::factory()->create(['title' => 'Lecturer', 'salary_grade' => 5]);

        $this->actingAs(hrHead())->put(route('job-titles.update', $title), [
            'title' => 'Lecturer', 'category' => 'faculty', 'unit' => "college:{$title->college_id}", 'salary_grade' => 6, 'is_active' => '1',
        ])->assertRedirect(route('job-titles.index'));

        $entry = AuditLog::query()->where('action', AuditAction::Update)->where('entity_id', $title->id)->sole();

        expect($title->fresh()->salary_grade)->toBe(6)
            ->and($entry->changes)->toEqual(['salary_grade' => ['old' => 5, 'new' => 6]]);
    });

    it('lets a title keep its own name', function () {
        $title = JobTitle::factory()->create(['salary_grade' => 5]);

        $this->actingAs(hrHead())->put(route('job-titles.update', $title), jobTitleInput(['title' => $title->title, 'unit' => "college:{$title->college_id}"]))
            ->assertSessionHasNoErrors();
    });

    it('is forbidden to HR Staff', function () {
        $title = JobTitle::factory()->create();

        $this->actingAs(hrStaff())->get(route('job-titles.edit', $title))->assertForbidden();
        $this->put(route('job-titles.update', $title), jobTitleInput())->assertForbidden();
    });
});

it('shows how many openings a title has and whether they are published', function () {
    JobTitle::factory()->create(['title' => 'Open Instructor', 'openings' => 2, 'is_published' => true]);
    JobTitle::factory()->create(['title' => 'Draft Instructor', 'openings' => 1, 'is_published' => false]);

    $this->actingAs(hrStaff())->get(route('job-titles.index'))
        ->assertSeeInOrder(['Open Instructor', '2 · Published'])
        ->assertSeeInOrder(['Draft Instructor', '1 · Not published']);
});
