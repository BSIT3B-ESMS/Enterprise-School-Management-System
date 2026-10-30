<?php

namespace Tests\Feature;

use App\Enums\Role;
use Tests\TestCase;

/** AJAX search: the office top-bar applicant search and the applicant's school suggestions */
class SearchTest extends TestCase
{
    public function test_office_quick_search_matches_every_word_and_is_staff_only(): void
    {
        $jordan = $this->makeApplicant(true);
        $this->makeApplicant(true, 'ana@example.com')->applicant->update(['first_name' => 'Ana', 'last_name' => 'Cruz']);
        $number = $this->applicationOf($jordan)->number;
        $officer = $this->makeUser(Role::Admission);

        $this->asUser($officer)->getJson('/api/admin/applicants/search?q=ana mendoza')->assertOk()->assertJsonCount(0, 'data');
        $this->asUser($officer)->getJson('/api/admin/applicants/search?q=jordan mendoza')
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $number)->assertJsonPath('data.0.name', 'Mendoza, Jordan')->assertJsonPath('data.0.lrn', '123456789012');
        $this->asUser($officer)->getJson('/api/admin/applicants/search?q='.$number)->assertJsonPath('data.0.id', $number);
        $this->asUser($officer)->getJson('/api/admin/applicants/search?q=123456789012')->assertJsonCount(1, 'data');
        $this->asUser($officer)->getJson('/api/admin/applicants/search?q=nobody')->assertOk()->assertJsonCount(0, 'data');
        // "%" is matched literally, not as "anything"
        $this->asUser($officer)->getJson('/api/admin/applicants/search?q=%25%25')->assertJsonCount(0, 'data');
        $this->asUser($officer)->getJson('/api/admin/applicants/search?q=j')->assertUnprocessable();

        $this->asUser($jordan)->getJson('/api/admin/applicants/search?q=jordan')->assertForbidden();
        $this->asUser($this->makeUser(Role::Clinic))->getJson('/api/admin/applicants/search?q=jordan')->assertForbidden();
    }

    public function test_school_suggestions_return_names_only(): void
    {
        $this->makeApplicant(true, 'ana@example.com');   // last school: Rizal High School
        $jordan = $this->makeApplicant();

        $this->asUser($jordan)->getJson('/api/applicant/schools?q=rizal')
            ->assertOk()->assertExactJson(['data' => ['Rizal High School']]);
        $this->asUser($jordan)->getJson('/api/applicant/schools?q=nowhere')->assertExactJson(['data' => []]);
        $this->asUser($this->makeUser(Role::Admission))->getJson('/api/applicant/schools?q=rizal')->assertForbidden();
    }
}
