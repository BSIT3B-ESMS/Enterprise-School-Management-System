<?php

namespace App\Support;

use App\Models\Program;
use Illuminate\Validation\Rule;

/**
 * The contract of the applicant portal's "My application" form
 * (#application-form): field names, the column each one is stored in, and
 * the validation for each of its three sections.
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
    ];

    public const SEXES = ['Female', 'Male', 'Prefer not to say'];

    public const CIVIL_STATUSES = ['Single', 'Married', 'Other'];

    public const SCHOOL_TYPES = ['Public', 'Private'];

    public const STRANDS = ['STEM', 'ABM', 'HUMSS', 'GAS', 'TVL – ICT', 'TVL – Other', 'Arts and Design', 'Sports', 'ALS / Not applicable'];

    public const GRAD_YEARS = ['2027 (expected)', '2026', '2025', '2024 or earlier'];

    public const CAMPUSES = ['Main Campus', 'North Campus'];

    private const PH_MOBILE = '/^(\+63|0)\s?9\d{2}\s?\d{3}\s?\d{4}$/';

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
            'strand' => ['nullable', Rule::in(self::STRANDS)],
            'gradYear' => ['nullable', Rule::in(self::GRAD_YEARS)],
            'gwa' => ['nullable', 'numeric', 'between:75,100'],
            'lrn' => ['nullable', 'regex:/^\d{12}$/', Rule::unique('applicants', 'lrn')->ignore($applicantId)],
            'program1' => ['nullable', Rule::in(self::programNames())],
            'program2' => ['nullable', Rule::in(self::programNames()), 'different:program1'],
            'program3' => ['nullable', Rule::in(self::programNames()), 'different:program1', 'different:program2'],
            'campus' => ['nullable', Rule::in(self::CAMPUSES)],
        ];
    }

    /** Fields each section needs before it can be marked complete (the form's `required`). */
    public const SECTION_REQUIRED = [
        1 => ['firstName', 'lastName', 'phone', 'birthDate', 'sex', 'civilStatus', 'address', 'city', 'province', 'guardianName', 'guardianPhone'],
        2 => ['lastSchool', 'schoolType', 'strand', 'gradYear', 'gwa'],
        3 => ['program1', 'program2', 'campus'],
    ];

    /** Names of programs applicants can choose (FR-02: open programs only). */
    public static function programNames(): array
    {
        return Program::where('is_open', true)->orderBy('name')->pluck('name')->all();
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
