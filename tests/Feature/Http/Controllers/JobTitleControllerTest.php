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
    RateTier::factory()->create(['salary_grade' => $grade, 'monthly_amount' => $first, 'effective_date' => $date]);
}

function jobTitleInput(array $overrides = []): array
{
    return [
        'title' => 'Lecturer',
        'category' => 'faculty',
        'units' => ['college:'.College::factory()->create()->id],
        'salary_grade' => 5,
        'is_active' => '1',
        ...$overrides,
    ];
}

beforeEach(fn () => seedPayBand(5));

describe('the list', function () {
    it('shows each title with its college or office and its salary grade and pay', function () {
        $college = College::factory()->create(['name' => 'College of Nursing']);
        JobTitle::factory()->create(['title' => 'Instructor I', 'college_id' => $college->id, 'salary_grade' => 5]);

        $this->actingAs(hrStaff())->get(route('job-titles.index'))
            ->assertOk()
            ->assertSee('Instructor I')
            ->assertSee('College of Nursing')
            ->assertSee('SG 5 · ₱30,000.00');
    });

    it('filters by search term, college or office, and category', function () {
        $college = College::factory()->create();
        $office = Office::factory()->create();
        JobTitle::factory()->create(['title' => 'Dean of Nursing', 'college_id' => $college->id]);
        JobTitle::factory()->nonTeaching()->create(['title' => 'Records Clerk', 'office_id' => $office->id]);

        $this->actingAs(hrAdmin());

        $this->get(route('job-titles.index', ['q' => 'Nursing']))->assertSee('Dean of Nursing')->assertDontSee('Records Clerk');
        $this->get(route('job-titles.index', ['unit' => "office:{$office->id}"]))->assertSee('Records Clerk')->assertDontSee('Dean of Nursing');
        $this->get(route('job-titles.index', ['category' => 'non_teaching']))->assertSee('Records Clerk')->assertDontSee('Dean of Nursing');
    });

    it('counts the people holding each title', function () {
        $title = JobTitle::factory()->create(['title' => 'Lecturer']);
        Employee::factory()->count(3)->create(['job_title_id' => $title->id]);

        $this->actingAs(hrAdmin())->get(route('job-titles.index'))->assertSeeInOrder(['Lecturer', '3']);
    });

    it('shows the add and edit controls to the HR Admin only', function () {
        $title = JobTitle::factory()->create();

        $this->actingAs(hrAdmin())->get(route('job-titles.index'))
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

        $this->actingAs(hrAdmin())->post(route('job-titles.store'), jobTitleInput(['units' => ["college:{$college->id}"]]))
            ->assertRedirect(route('job-titles.index'));

        $title = JobTitle::query()->where('title', 'Lecturer')->sole();

        expect($title->college_id)->toBe($college->id)
            ->and($title->office_id)->toBeNull()
            ->and($title->category)->toBe(JobTitleCategory::Faculty)
            ->and(AuditLog::query()->where('action', AuditAction::Create)->where('entity_id', $title->id)->exists())->toBeTrue();
    });

    it('saves a title that belongs to an office', function () {
        $office = Office::factory()->create();

        $this->actingAs(hrAdmin())->post(route('job-titles.store'), jobTitleInput(['units' => ["office:{$office->id}"], 'category' => 'non_teaching']));

        $title = JobTitle::query()->where('title', 'Lecturer')->sole();

        expect($title->office_id)->toBe($office->id)->and($title->college_id)->toBeNull();
    });

    it('rejects :scenario', function (array $override, string $field) {
        $this->actingAs(hrAdmin())->post(route('job-titles.store'), jobTitleInput($override))->assertSessionHasErrors($field);

        expect(JobTitle::query()->count())->toBe(0);
    })->with([
        'a missing title' => [['title' => ''], 'title'],
        'an unknown category' => [['category' => 'janitor'], 'category'],
        'no college or office' => [['units' => []], 'units'],
        'a malformed college or office' => [['units' => ['everywhere']], 'units.0'],
        'a college that does not exist' => [['units' => ['college:999999']], 'units'],
        'a pay band that does not exist' => [['salary_grade' => 77], 'salary_grade'],
    ]);

    it('rejects a name already used in the same college but allows it in another', function () {
        $college = College::factory()->create();
        JobTitle::factory()->create(['title' => 'Lecturer', 'college_id' => $college->id]);

        $this->actingAs(hrAdmin())->post(route('job-titles.store'), jobTitleInput(['units' => ["college:{$college->id}"]]))
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

        $this->actingAs(hrAdmin())->put(route('job-titles.update', $title), [
            'title' => 'Lecturer', 'category' => 'faculty', 'units' => ["college:{$title->college_id}"], 'salary_grade' => 6, 'is_active' => '1',
        ])->assertRedirect(route('job-titles.index'));

        $entry = AuditLog::query()->where('action', AuditAction::Update)->where('entity_id', $title->id)->sole();

        expect($title->fresh()->salary_grade)->toBe(6)
            ->and($entry->changes)->toEqual(['salary_grade' => ['old' => 5, 'new' => 6]]);
    });

    it('lets a title keep its own name', function () {
        $title = JobTitle::factory()->create(['salary_grade' => 5]);

        $this->actingAs(hrAdmin())->put(route('job-titles.update', $title), jobTitleInput(['title' => $title->title, 'units' => ["college:{$title->college_id}"]]))
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
        ->assertSeeInOrder(['Open Instructor', '2 open'])
        ->assertSeeInOrder(['Draft Instructor', '1 open']);
});

describe('a shared job title', function () {
    it('is added once for several colleges and offices and listed as one row', function () {
        $colleges = College::factory()->count(2)->create();
        $this->actingAs(hrAdmin())->post(route('job-titles.store'), jobTitleInput(['title' => 'Instructor I', 'units' => $colleges->map(fn ($college) => "college:{$college->id}")->all()]))->assertSessionHasNoErrors();

        expect(JobTitle::query()->where('title', 'Instructor I')->count())->toBe(2);

        $this->get(route('job-titles.index'))->assertSee('2 units')->assertSee($colleges[0]->code)->assertSee($colleges[1]->code);
    });

    it('changes the name, salary grade, and status in every college when edited once', function () {
        seedPayBand(6, 40000, 47000);
        $first = JobTitle::factory()->create(['title' => 'Instructor I', 'salary_grade' => 5]);
        $second = JobTitle::factory()->create(['title' => 'Instructor I', 'salary_grade' => 5]);

        $this->actingAs(hrAdmin())->put(route('job-titles.update', $first), [
            'title' => 'Instructor One', 'category' => 'faculty', 'salary_grade' => 6, 'units' => ["college:{$first->college_id}", "college:{$second->college_id}"],
        ])->assertRedirect(route('job-titles.index'));

        expect($first->fresh()->title)->toBe('Instructor One')
            ->and($second->fresh()->title)->toBe('Instructor One')
            ->and($second->fresh()->salary_grade)->toBe(6)
            ->and($second->fresh()->is_active)->toBeFalse();
    });

    it('adds a college by ticking it and refuses to remove one that has people', function () {
        $first = JobTitle::factory()->create(['title' => 'Instructor I', 'salary_grade' => 5]);
        $second = JobTitle::factory()->create(['title' => 'Instructor I', 'salary_grade' => 5]);
        $third = College::factory()->create();
        Employee::factory()->create(['job_title_id' => $second->id]);
        $this->actingAs(hrAdmin());
        $base = ['title' => 'Instructor I', 'category' => 'faculty', 'salary_grade' => 5, 'is_active' => '1'];

        $this->put(route('job-titles.update', $first), $base + ['units' => ["college:{$first->college_id}", "college:{$third->id}"]])->assertSessionHasErrors('units');
        expect(JobTitle::query()->where('title', 'Instructor I')->count())->toBe(2);

        $this->put(route('job-titles.update', $first), $base + ['units' => ["college:{$first->college_id}", "college:{$second->college_id}", "college:{$third->id}"]])->assertSessionHasNoErrors();
        expect(JobTitle::query()->where('title', 'Instructor I')->count())->toBe(3);

        $this->put(route('job-titles.update', $first), $base + ['units' => ["college:{$second->college_id}", "college:{$third->id}"]])->assertSessionHasNoErrors();
        expect(JobTitle::query()->where('title', 'Instructor I')->count())->toBe(2);
    });
});
