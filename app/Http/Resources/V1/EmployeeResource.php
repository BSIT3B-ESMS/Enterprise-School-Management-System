<?php

namespace App\Http\Resources\V1;

use App\Models\Employee;
use App\Models\RateTier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The employee fields an external system may read, and nothing else: a column added to employees later never reaches
 * the API unless it is listed here.
 *
 * @mixin Employee
 */
class EmployeeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $schedule = $request->attributes->get('rate_schedule') ?? RateTier::scheduleOn();
        $grade = $this->jobTitle->salary_grade;

        return [
            'employee_no' => $this->employee_no,
            'full_name' => $this->full_name,
            'employment_type' => $this->employment_type->value,
            'college' => $this->college ? ['code' => $this->college->code, 'name' => $this->college->name] : null,
            'office' => $this->office ? ['code' => $this->office->code, 'name' => $this->office->name] : null,
            'job_title' => $this->jobTitle->title,
            'base_rate' => isset($schedule[$grade]) ? (float) $schedule[$grade] : null,
            'status' => $this->effectiveStatus(),
            'status_detail' => $this->statusDetail(),
            'hired_on' => $this->hired_on?->toDateString(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
