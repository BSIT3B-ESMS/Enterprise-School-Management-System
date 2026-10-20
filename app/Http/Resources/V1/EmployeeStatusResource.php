<?php

namespace App\Http\Resources\V1;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Employee
 */
class EmployeeStatusResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'employee_no' => $this->employee_no,
            'status' => $this->effectiveStatus(),
            'status_detail' => $this->statusDetail(),
        ];
    }
}
