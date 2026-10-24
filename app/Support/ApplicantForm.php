<?php

namespace App\Support;

use App\Models\Barangay;
use App\Models\Program;
use App\Models\Strand;
use Illuminate\Validation\Rule;

/**
 * The contract of the applicant portal's "My application" form
 * (#application-form): field names, the column each one is stored in, and
 * the validation for each of its three sections. Strands and barangays
 * come from the lists staff maintain (Strand, Barangay; FR-26).
 */
class ApplicantForm
{
    /** Form field name => applicants column. */
    public const PROFILE_FIELDS = [
        'firstName' => 'first_name',
        'middleName' => 'middle_name',
        'lastName' => 'last_name',
        'phone' => 'phone',
        'birthDate' => 'birthday',
        'sex' => 'sex',
        'civilStatus' => 'civil_status',
        'address' => 'address',
        'city' => 'city',
        'province' => 'province',
        'zip' => 'zip',
        'guardianName' => 'guardian_name',
        'guardianPhone' => 'guardian_phone',
        'lastSchool' => 'last_school',
        'schoolType' => 'school_type',
        'strand' => 'strand',
        'gradYear' => 'grad_year',
        'gwa' => 'gwa',
        'lrn' => 'lrn',
        'isResident' => 'is_resident',
        'district' => 'district',
        'barangay' => 'barangay',
        'hasGuardian' => 'has_guardian',
        'guardianConsentName' => 'guardian_consent_name',
    ];

    public const SEXES = ['Female', 'Male', 'Prefer not to say'];

    public const CIVIL_STATUSES = ['Single', 'Married', 'Other'];

    public const SCHOOL_TYPES = ['Public', 'Private'];

    public const GRAD_YEARS = ['2027 (expected)', '2026', '2025', '2024 or earlier'];

    public const CAMPUSES = ['Main Campus', 'North Campus'];

    public const PH_MOBILE = '/^(\+63|0)\s?9\d{2}\s?\d{3}\s?\d{4}$/';

    /**
     * Format rules, used for autosave: a value that breaks one is skipped
     * (the applicant is mid-typing), the rest of the form is still saved.
     */
    public static function formatRules(?int $applicantId = null): array
    {
        return [
            'firstName' => ['string', 'max:100'],
            'middleName' => ['nullable', 'string', 'max:100'],
            'lastName' => ['string', 'max:100'],
            'phone' => ['nullable', 'regex:'.self::PH_MOBILE],
            'birthDate' => ['nullable', 'date', 'before:-14 years', 'after:-80 years'],
            'sex' => ['nullable', Rule::in(self::SEXES)],
            'civilStatus' => ['nullable', Rule::in(self::CIVIL_STATUSES)],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'zip' => ['nullable', 'regex:/^\d{4}$/'],
            'guardianName' => ['nullable', 'string', 'max:150'],
            'guardianPhone' => ['nullable', 'regex:'.self::PH_MOBILE],
            'lastSchool' => ['nullable', 'string', 'max:255'],
            'schoolType' => ['nullable', Rule::in(self::SCHOOL_TYPES)],
            'strand' => ['nullable', Rule::in(Strand::activeNames())],
            'gradYear' => ['nullable', Rule::in(self::GRAD_YEARS)],
            'gwa' => ['nullable', 'numeric', 'between:75,100'],
            'lrn' => ['nullable', 'regex:/^\d{12}$/', Rule::unique('applicants', 'lrn')->ignore($applicantId)],
            'isResident' => ['nullable', 'boolean'],
            'district' => ['nullable', Rule::in([1, 2])],
            'barangay' => ['nullable', Rule::in(array_merge(...array_values(Barangay::byDistrict())))],
            'hasGuardian' => ['nullable', 'boolean'],
            'guardianConsentName' => ['nullable', 'string', 'max:150'],
            'guardianConsent' => ['nullable', Rule::in(['0', '1'])],
            'program1' => ['nullable', Rule::in(self::programNames())],
            'program2' => ['nullable', Rule::in(self::programNames()), 'different:program1'],
            'program3' => ['nullable', Rule::in(self::programNames()), 'different:program1', 'different:program2'],
            'campus' => ['nullable', Rule::in(self::CAMPUSES)],
        ];
    }

    /** Messages for checks only the server can make; the rest mirror the form's own wording. */
    public const MESSAGES = [
        'lrn.unique' => 'This LRN is already used by another application.',
        'lrn.regex' => 'The LRN has exactly 12 digits.',
        'phone.regex' => 'Use a PH mobile number, like +63 917 123 4567.',
        'guardianPhone.regex' => 'Use a PH mobile number, like +63 917 123 4567.',
        'zip.regex' => 'ZIP codes have 4 digits.',
        'program2.different' => 'Pick a different program from your first choice.',
        'program3.different' => 'Pick a program different from your first and second choices.',
        'program3.in' => 'This program is not open for applications.',
        'barangay.in' => 'Pick a barangay in your district.',
        'program1.in' => 'This program is not open for applications.',
        'program2.in' => 'This program is not open for applications.',
        'birthDate.before' => 'Check your date of birth.',
        'birthDate.after' => 'Check your date of birth.',
        'guardianConsentName.required' => "Enter the name of the parent or guardian giving consent.",
        'guardianConsent.required' => 'Applicants under 18 need a parent or guardian to consent.',
        'guardianConsent.in' => 'Applicants under 18 need a parent or guardian to consent.',
    ];

    /**
     * Fields each section needs before it can be marked complete (the form's `required`).
     * Pasig residents also need district and barangay (ApplicationService::assertSectionsComplete).
     * gradYear stays: it decides Form 137 (graduate) vs Form 138 (Grade 12).
     */
    public const SECTION_REQUIRED = [
        1 => ['lastName', 'firstName', 'sex', 'phone', 'isResident', 'address', 'birthDate'],
        2 => ['lrn', 'schoolType', 'lastSchool', 'strand', 'gradYear'],
        3 => ['program1', 'program2', 'program3'],
    ];

    /** Names of programs applicants can choose (FR-02: open programs only). */
    public static function programNames(): array
    {
        return Program::where('is_open', true)->orderBy('name')->pluck('name')->all();
    }

    /** FR-31: under 18 on the given date (today), so a parent or guardian must consent. */
    public static function isMinor(?string $birthDate): bool
    {
        return $birthDate !== null && $birthDate !== '' && strtotime($birthDate) !== false
            && now()->subYears(18)->lt(\Illuminate\Support\Carbon::parse($birthDate));
    }

    /** "2027 (expected)" means still in Grade 12; anything else is a graduate. */
    public static function applicantType(?string $gradYear): ?string
    {
        if ($gradYear === null || $gradYear === '') {
            return null;
        }

        return str_contains($gradYear, 'expected') ? 'grade12' : 'graduate';
    }
}
