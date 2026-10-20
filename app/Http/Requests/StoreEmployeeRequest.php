<?php

namespace App\Http\Requests;

use App\Enums\EmploymentType;
use App\Models\Employee;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends EmployeeRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Employee::class);
    }

    /**
     * A new employee is given a job title (which sets the college or office), an employment type, and a salary step.
     * A person can also belong to the other side: a college teacher based in an office, or office staff who teach.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->personalRules(),
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'job_title_id' => ['required', 'integer', Rule::exists('job_titles', 'id')->where('is_active', true)],
            'extra_college_id' => ['nullable', 'integer', 'exists:colleges,id'],
            'extra_office_id' => ['nullable', 'integer', 'exists:offices,id'],
            'salary_step' => ['required', 'integer', 'between:1,8'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [...parent::messages(), 'job_title_id.required' => 'Choose a job title.', 'job_title_id.exists' => 'Choose an active job title from the list.'];
    }
}
