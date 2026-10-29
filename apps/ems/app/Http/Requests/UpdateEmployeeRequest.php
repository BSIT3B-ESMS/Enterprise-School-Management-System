<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Gate;

class UpdateEmployeeRequest extends EmployeeRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('employee'));
    }

    /**
     * Editing changes personal and contact details only. The job title, college, office, salary step, and status are
     * changed by the HR Admin through their own forms.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->personalRules();
    }
}
