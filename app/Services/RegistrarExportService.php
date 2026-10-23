<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Models\AdmissionPeriod;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * FR-29: admitted applicants in the layout the Registrar receives (CSV,
 * XLSX, or the integration API). The column list is the agreed layout;
 * change it here when the Registrar confirms theirs. Scores never leave.
 */
class RegistrarExportService
{
    /** key => heading */
    public const COLUMNS = [
        'applicationNumber' => 'Application no.',
        'lrn' => 'LRN',
        'lastName' => 'Last name',
        'firstName' => 'First name',
        'middleName' => 'Middle name',
        'sex' => 'Sex',
        'birthday' => 'Birthday',
        'email' => 'Email',
        'phone' => 'Mobile',
        'address' => 'Address',
        'pasigResident' => 'Pasig resident',
        'district' => 'District',
        'barangay' => 'Barangay',
        'lastSchool' => 'Last school attended',
        'schoolType' => 'School type',
        'strand' => 'SHS strand',
        'programCode' => 'Program code',
        'program' => 'Program',
        'admittedAt' => 'Admitted on',
        'studentNumber' => 'Student number',
    ];

    /** Admitted applications of the current period, oldest admission first. */
    public function admitted(?string $since = null): Builder
    {
        return DB::table('applications as a')
            ->join('applicants as p', 'p.id', '=', 'a.applicant_id')
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->leftJoin('programs as g', 'g.id', '=', 'a.offered_program_id')
            ->where('a.admission_period_id', AdmissionPeriod::current()->id)
            ->where('a.status', ApplicationStatus::Admitted->value)
            ->whereNull('a.deleted_at')
            ->when($since, fn ($q) => $q->where('a.admitted_at', '>=', Carbon::parse($since)->utc()))
            ->orderBy('a.admitted_at')->orderBy('a.id')
            ->select(['a.number', 'a.admitted_at', 'a.student_number', 'p.lrn', 'p.last_name', 'p.first_name', 'p.middle_name', 'p.sex',
                'p.birthday', 'p.phone', 'p.address', 'p.is_resident', 'p.district', 'p.barangay', 'p.last_school', 'p.school_type',
                'p.strand', 'u.email', 'g.code as program_code', 'g.name as program_name']);
    }

    public function record(object $r): array
    {
        return [
            'applicationNumber' => $r->number,
            'lrn' => $r->lrn,
            'lastName' => $r->last_name,
            'firstName' => $r->first_name,
            'middleName' => $r->middle_name,
            'sex' => $r->sex,
            'birthday' => $r->birthday ? Carbon::parse($r->birthday)->toDateString() : null,
            'email' => $r->email,
            'phone' => $r->phone,
            'address' => $r->address,
            'pasigResident' => $r->is_resident === null ? null : (bool) $r->is_resident,
            'district' => $r->district,
            'barangay' => $r->barangay,
            'lastSchool' => $r->last_school,
            'schoolType' => $r->school_type,
            'strand' => $r->strand,
            'programCode' => $r->program_code,
            'program' => $r->program_name,
            'admittedAt' => $r->admitted_at ? Carbon::parse($r->admitted_at)->setTimezone(config('admission.display_timezone'))->toDateString() : null,
            'studentNumber' => $r->student_number,
        ];
    }

    /** As a report (ReportService): headings and rows. */
    public function report(): array
    {
        return [
            'columns' => array_values(self::COLUMNS),
            'rows' => $this->admitted()->get()->map(function ($r) {
                $row = $this->record($r);
                $row['pasigResident'] = $row['pasigResident'] === null ? '' : ($row['pasigResident'] ? 'Yes' : 'No');

                return array_values($row);
            })->all(),
        ];
    }
}
