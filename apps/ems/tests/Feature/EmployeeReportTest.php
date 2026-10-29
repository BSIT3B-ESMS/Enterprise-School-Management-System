<?php

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\College;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\RateTier;

beforeEach(fn () => $this->actingAs(hrAdmin()));

function reportRows($response): array
{
    preg_match('/data-rows="([^"]*)"/', $response->getContent(), $match);

    return json_decode(html_entity_decode($match[1]), true);
}

it('links Generate Report from the Employees page with the current filters', function () {
    $this->get(route('employees.index', ['q' => 'Santos']))
        ->assertSee('href="'.route('employees.report', ['q' => 'Santos']).'"', escape: false)
        ->assertDontSee('employee-report-modal', escape: false);
});

it('renders the report page with a title and a way back that keeps the filters', function () {
    $this->get(route('employees.report', ['q' => 'Santos']))
        ->assertOk()
        ->assertSee('Generate Report')
        ->assertSee('aria-label="Back to employees"', escape: false)
        ->assertSee('Back to Employees</a>', escape: false)
        ->assertSee('href="'.route('employees.index', ['q' => 'Santos']).'"', escape: false);
});

it('offers pdf, excel, and csv with pdf selected by default', function () {
    $this->get(route('employees.report'))
        ->assertSeeInOrder([
            'Export Format', 'Selected:', 'data-report-format-label>PDF',
            'value="pdf" autocomplete="off" checked>', 'PDF</label>',
            'value="xlsx" autocomplete="off">', 'Excel (.xlsx)</label>',
            'value="csv" autocomplete="off">', 'CSV</label>',
        ], escape: false);
});

it('stacks export format, college and job title choices, the preview, the fields, then the actions', function () {
    $this->get(route('employees.report'))
        ->assertSeeInOrder([
            'Export Format', 'value="pdf"', 'value="xlsx"', 'value="csv"',
            'Employees to Include', 'data-report-filter="college"', 'data-report-filter="office"', 'data-report-filter="jobTitle"',
            'Report Preview', 'data-report-count', 'data-report-preview',
            'Fields to Include', 'data-report-select-all>Select All', 'data-report-fields',
            'data-report-error', 'Back to Employees</a>', 'data-report-generate',
        ], escape: false);
});

it('names the address the export is logged to', function () {
    $this->get(route('employees.report'))->assertSee('data-export-url="'.route('exports.store').'"', escape: false);
});

it('keeps the employees sidebar link current on the report page', function () {
    $this->get(route('employees.report'))
        ->assertSee('aria-current="page" href="'.route('employees.index').'"', escape: false);
});

describe('the rows', function () {
    it('are the employees the Employees page would list for the same filters', function () {
        $college = College::factory()->create();
        Employee::factory()->for(JobTitle::factory()->state(['college_id' => $college->id]))->create(['last_name' => 'Inside']);
        Employee::factory()->create(['last_name' => 'Outside']);
        Employee::factory()->inactive()->create(['last_name' => 'Archived']);

        $rows = reportRows($this->get(route('employees.report', ['college' => $college->id])));

        expect(collect($rows)->pluck('name')->implode(' '))->toContain('Inside')->not->toContain('Outside')->not->toContain('Archived');
    });

    it('carry the base pay from the salary grade (Step 1), and only the last four digits of government IDs', function () {
        RateTier::factory()->create(['salary_grade' => 12, 'monthly_amount' => 30300, 'effective_date' => '2020-01-01']);
        $employee = Employee::factory()->create();
        $employee->forceFill(['government_ids' => null, 'government_id_hints' => null]);
        $employee->setGovernmentIds(['tin' => '123-456-789-000']);
        $employee->save();

        $response = $this->get(route('employees.report'));
        $row = reportRows($response)[0];

        expect($row['basePay'])->toEqual(30300)
            ->and($row['ids']['tin'])->toBe('•••• 9000')
            ->and($row['ids']['sss'])->toBe('');
        $response->assertDontSee('123-456-789-000');
    });

    it('say in words which filters were applied', function () {
        $response = $this->get(route('employees.report', ['q' => 'Santos', 'employment_type' => 'part_time', 'sort' => 'hired', 'dir' => 'desc']));
        preg_match('/data-summary="([^"]*)"/', $response->getContent(), $match);
        $summary = json_decode(html_entity_decode($match[1]), true);

        expect($summary['filters'])->toBe(['Faculty', 'Search: “Santos”', 'Part-time only'])
            ->and($summary['sort'])->toBe('Sorted by Date Hired, descending');
    });
});

describe('logging an export', function () {
    it('writes the report, format, count, and fields to the audit log', function () {
        $head = hrAdmin();

        $this->actingAs($head)->postJson(route('exports.store'), ['report' => 'employees', 'format' => 'csv', 'count' => 12, 'fields' => ['id', 'name']])
            ->assertNoContent();

        $entry = AuditLog::query()->where('action', AuditAction::Export)->sole();

        expect($entry->user_id)->toBe($head->id)
            ->and($entry->changes)->toEqual(['report' => 'employees', 'format' => 'csv', 'count' => 12, 'fields' => ['id', 'name']]);
    });

    it('rejects an unknown report or format', function (array $override) {
        $this->postJson(route('exports.store'), ['report' => 'employees', 'format' => 'csv', 'count' => 1, ...$override])->assertUnprocessable();

        expect(AuditLog::query()->count())->toBe(0);
    })->with([[['report' => 'payroll']], [['format' => 'docx']], [['count' => -1]]]);

    it('is closed to HR Staff and to visitors', function () {
        $payload = ['report' => 'employees', 'format' => 'csv', 'count' => 1];

        $this->actingAs(hrStaff())->postJson(route('exports.store'), $payload)->assertForbidden();
        auth()->logout();
        $this->postJson(route('exports.store'), $payload)->assertUnauthorized();
    });

    it('is closed to HR Staff on the report page too', function () {
        $this->actingAs(hrStaff())->get(route('employees.report'))->assertForbidden();
    });
});
