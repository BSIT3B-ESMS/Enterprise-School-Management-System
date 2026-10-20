<?php

use App\Actions\Employees\TransferEmployee;
use App\Enums\AuditAction;
use App\Enums\EmploymentType;
use App\Enums\OfferKind;
use App\Enums\OfferStatus;
use App\Models\AuditLog;
use App\Models\College;
use App\Models\CollegeTransfer;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Offer;
use App\Models\Office;
use App\Models\User;
use Illuminate\Support\Carbon;

function changeInput(JobTitle $title, array $overrides = []): array
{
    return [
        'job_title_id' => $title->id,
        'employment_type' => 'full_time',
        'effective_date' => now()->toDateString(),
        'reason' => 'Reassigned',
        ...$overrides,
    ];
}

function employeeWithAccount(array $state = []): array
{
    $employee = Employee::factory()->create($state);
    $user = User::factory()->create(['employee_id' => $employee->id]);

    return [$employee, $user];
}

describe('the transfer record itself', function () {
    it('moves the employee at once when the date is today, and keeps the transfer as history', function () {
        $head = hrAdmin();
        $employee = Employee::factory()->create();
        $oldTitle = $employee->jobTitle;
        $newCollege = College::factory()->create();
        $newTitle = JobTitle::factory()->create(['college_id' => $newCollege->id]);

        $transfer = app(TransferEmployee::class)->handle($head, $employee, $newTitle, null, null, now(), 'Reassigned');

        expect($employee->fresh()->college_id)->toBe($newCollege->id)
            ->and($employee->fresh()->job_title_id)->toBe($newTitle->id)
            ->and($transfer->from_job_title_id)->toBe($oldTitle->id)
            ->and($transfer->to_college_id)->toBe($newCollege->id)
            ->and($transfer->recorded_by)->toBe($head->id)
            ->and($transfer->applied_at)->not->toBeNull()
            ->and(AuditLog::query()->where('action', AuditAction::Create)->where('entity_type', (new CollegeTransfer)->getMorphClass())->exists())->toBeTrue();
    });

    it('records a future-dated transfer without changing the employee yet', function () {
        $employee = Employee::factory()->create();

        $transfer = app(TransferEmployee::class)->handle(hrAdmin(), $employee, JobTitle::factory()->create(), null, null, now()->addMonth(), null);

        expect($employee->fresh()->job_title_id)->toBe($employee->job_title_id)
            ->and($transfer->applied_at)->toBeNull();
    });
});

describe('proposing a change of assignment', function () {
    it('sends the employee an agreement and changes nothing until they accept', function () {
        [$employee, $user] = employeeWithAccount();
        $newTitle = JobTitle::factory()->create();

        $this->actingAs(hrAdmin())->post(route('employees.transfer', $employee), changeInput($newTitle))
            ->assertRedirect(route('employees.show', $employee))
            ->assertSessionHas('success');

        $offer = Offer::query()->sole();

        expect($offer->kind)->toBe(OfferKind::Change)
            ->and($offer->status)->toBe(OfferStatus::Pending)
            ->and($offer->summary['current']['position'])->toBe($employee->jobTitle->title)
            ->and($offer->summary['position'])->toBe($newTitle->title)
            ->and($employee->fresh()->job_title_id)->toBe($employee->job_title_id)
            ->and($employee->transfers()->count())->toBe(0)
            ->and($user->notifications()->count())->toBe(1);
    });

    it('applies the change, as a transfer, when the employee signs', function () {
        [$employee, $user] = employeeWithAccount(['first_name' => 'Maria', 'last_name' => 'Santos']);
        $newTitle = JobTitle::factory()->create();
        $this->actingAs(hrAdmin())->post(route('employees.transfer', $employee), changeInput($newTitle));
        $offer = Offer::query()->sole();

        $this->actingAs($user)->post(route('employee.offers.accept', $offer), ['signed_name' => 'Maria Santos', 'agree' => '1'])->assertSessionHas('success');

        expect($offer->fresh()->status)->toBe(OfferStatus::Accepted)
            ->and($offer->fresh()->signed_name)->toBe('Maria Santos')
            ->and($offer->fresh()->applied_at)->not->toBeNull()
            ->and($employee->fresh()->job_title_id)->toBe($newTitle->id)
            ->and($employee->transfers()->sole()->applied_at)->not->toBeNull();
    });

    it('needs the employee to type their own name to sign', function () {
        [$employee, $user] = employeeWithAccount(['first_name' => 'Maria', 'last_name' => 'Santos']);
        $this->actingAs(hrAdmin())->post(route('employees.transfer', $employee), changeInput(JobTitle::factory()->create()));
        $offer = Offer::query()->sole();

        $this->actingAs($user)->post(route('employee.offers.accept', $offer), ['signed_name' => 'Someone Else', 'agree' => '1'])->assertSessionHas('error');
        $this->post(route('employee.offers.accept', $offer), ['signed_name' => 'Maria Santos'])->assertSessionHasErrors('agree');

        expect($offer->fresh()->status)->toBe(OfferStatus::Pending);
    });

    it('leaves the employee exactly as they are when they decline', function () {
        [$employee, $user] = employeeWithAccount();
        $this->actingAs(hrAdmin())->post(route('employees.transfer', $employee), changeInput(JobTitle::factory()->create()));
        $offer = Offer::query()->sole();

        $this->actingAs($user)->post(route('employee.offers.decline', $offer), ['reason' => 'Prefer to stay'])->assertSessionHas('success');

        expect($offer->fresh()->status)->toBe(OfferStatus::Declined)
            ->and($employee->fresh()->job_title_id)->toBe($employee->job_title_id)
            ->and($employee->transfers()->count())->toBe(0);
    });

    it('holds an accepted change with a future start date until that day', function () {
        [$employee, $user] = employeeWithAccount(['first_name' => 'Maria', 'last_name' => 'Santos']);
        $newTitle = JobTitle::factory()->create();
        $this->actingAs(hrAdmin())->post(route('employees.transfer', $employee), changeInput($newTitle, ['effective_date' => now()->addDays(10)->toDateString()]));
        $this->actingAs($user)->post(route('employee.offers.accept', Offer::query()->sole()), ['signed_name' => 'Maria Santos', 'agree' => '1']);

        expect($employee->fresh()->job_title_id)->toBe($employee->job_title_id);

        $this->travelTo(now()->addDays(11));
        $this->artisan('agreements:apply')->assertSuccessful();

        expect($employee->fresh()->job_title_id)->toBe($newTitle->id);
    });

    it('lets a full-time employee become part-time only with an end of contract', function () {
        [$employee] = employeeWithAccount();
        $title = $employee->jobTitle;

        $this->actingAs(hrAdmin())->post(route('employees.transfer', $employee), changeInput($title, ['employment_type' => 'part_time', 'weekly_hours' => 18]))
            ->assertSessionHas('error');

        expect(Offer::query()->count())->toBe(0);

        $this->post(route('employees.transfer', $employee), changeInput($title, ['employment_type' => 'part_time', 'weekly_hours' => 18, 'contract_term' => 'second_semester', 'academic_year' => 2026]))
            ->assertSessionHas('success');

        $offer = Offer::query()->sole();

        expect($offer->employment_type)->toBe(EmploymentType::PartTime)
            ->and($offer->end_date->toDateString())->toBe('2027-03-31')
            ->and($offer->summary['term'])->toBe('2nd Semester, AY 2026-2027');
    });

    it('applies the new employment type and contract end when a part-time change is signed', function () {
        [$employee, $user] = employeeWithAccount(['first_name' => 'Maria', 'last_name' => 'Santos']);
        $this->actingAs(hrAdmin())->post(route('employees.transfer', $employee), changeInput($employee->jobTitle, ['employment_type' => 'part_time', 'weekly_hours' => 18, 'end_date' => now()->addMonths(5)->toDateString()]));
        $this->actingAs($user)->post(route('employee.offers.accept', Offer::query()->sole()), ['signed_name' => 'Maria Santos', 'agree' => '1']);

        $fresh = $employee->fresh();

        expect($fresh->employment_type)->toBe(EmploymentType::PartTime)
            ->and($fresh->weekly_hours)->toBe(18)
            ->and($fresh->contract_ends_on->toDateString())->toBe(now()->addMonths(5)->toDateString())
            ->and($fresh->transfers()->count())->toBe(0);
    });

    it('moves office staff into a title in another office, keeping an extra college, once they sign', function () {
        $employee = Employee::factory()->nonTeaching()->create(['first_name' => 'Ana', 'last_name' => 'Cruz']);
        $user = User::factory()->create(['employee_id' => $employee->id]);
        $college = College::factory()->create();
        $title = JobTitle::factory()->nonTeaching()->create(['office_id' => Office::factory()->create()->id]);

        $this->actingAs(hrAdmin())->post(route('employees.transfer', $employee), changeInput($title, ['extra_college_id' => $college->id]));
        $this->actingAs($user)->post(route('employee.offers.accept', Offer::query()->sole()), ['signed_name' => 'Ana Cruz', 'agree' => '1']);

        expect([$employee->fresh()->office_id, $employee->fresh()->college_id])->toBe([$title->office_id, $college->id]);
    });

    it('refuses a change that is not a change, and a second offer while one is waiting', function () {
        [$employee] = employeeWithAccount();

        $this->actingAs(hrAdmin())->post(route('employees.transfer', $employee), changeInput($employee->jobTitle))->assertSessionHas('error');
        $this->post(route('employees.transfer', $employee), changeInput(JobTitle::factory()->create()))->assertSessionHas('success');
        $this->post(route('employees.transfer', $employee), changeInput(JobTitle::factory()->create()))->assertSessionHas('error');

        expect(Offer::query()->count())->toBe(1);
    });

    it('can be withdrawn while it is waiting', function () {
        [$employee] = employeeWithAccount();
        $this->actingAs(hrAdmin())->post(route('employees.transfer', $employee), changeInput(JobTitle::factory()->create()));

        $this->post(route('employees.offers.withdraw', [$employee, Offer::query()->sole()]))->assertSessionHas('success');

        expect(Offer::query()->sole()->status)->toBe(OfferStatus::Withdrawn);
    });

    it('rejects a missing title, date, or employment type', function () {
        [$employee] = employeeWithAccount();

        $this->actingAs(hrAdmin())->post(route('employees.transfer', $employee), ['reason' => 'x'])->assertSessionHasErrors(['job_title_id', 'effective_date', 'employment_type']);
    });

    it('is forbidden to HR Staff', function () {
        [$employee] = employeeWithAccount();

        $this->actingAs(hrStaff())->post(route('employees.transfer', $employee), changeInput(JobTitle::factory()->create()))->assertForbidden();

        expect(Offer::query()->count())->toBe(0);
    });

    it('keeps another employee from answering the offer', function () {
        [$employee] = employeeWithAccount();
        $this->actingAs(hrAdmin())->post(route('employees.transfer', $employee), changeInput(JobTitle::factory()->create()));

        $this->actingAs(employeeUser())->post(route('employee.offers.accept', Offer::query()->sole()), ['signed_name' => 'X', 'agree' => '1'])->assertNotFound();
    });
});

describe('a new contract', function () {
    it('offers a part-time employee a renewal that moves the contract end when signed', function () {
        [$employee, $user] = employeeWithAccount(['first_name' => 'Maria', 'last_name' => 'Santos', 'employment_type' => 'part_time', 'weekly_hours' => 20, 'contract_ends_on' => Carbon::parse('2026-03-31')]);

        $this->actingAs(hrAdmin())->post(route('employees.renew', $employee), changeInput($employee->jobTitle, ['employment_type' => 'part_time', 'weekly_hours' => 20, 'contract_term' => 'academic_year', 'academic_year' => 2026]))->assertSessionHas('success');
        $offer = Offer::query()->sole();

        expect($offer->kind)->toBe(OfferKind::Renewal);

        $this->actingAs($user)->post(route('employee.offers.accept', $offer), ['signed_name' => 'Maria Santos', 'agree' => '1']);

        expect($employee->fresh()->contract_ends_on->toDateString())->toBe('2027-03-31');
    });

    it('shows the employee the offers and agreements page', function () {
        [$employee, $user] = employeeWithAccount();
        $this->actingAs(hrAdmin())->post(route('employees.transfer', $employee), changeInput(JobTitle::factory()->create()));

        $this->actingAs($user)->get(route('employee.offers'))->assertOk()->assertSee('Change of assignment');
        $this->get(route('employee.offers.show', Offer::query()->sole()))->assertOk()->assertSee('Your current terms')->assertSee('Terms and Conditions');
    });
});
