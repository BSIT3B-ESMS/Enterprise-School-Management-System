<?php

namespace App\Http\Requests;

use App\Enums\MaritalStatus;
use App\Enums\Sex;
use App\Http\Requests\Concerns\GovernmentIdRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The fields of an employee record that HR Staff and the HR Admin both enter: name, personal details, contact details,
 * and government ID numbers. Subclasses add what they need (the job assignment when creating).
 */
abstract class EmployeeRequest extends FormRequest
{
    use GovernmentIdRules;

    /**
     * @return array<string, mixed>
     */
    protected function personalRules(): array
    {
        return [
            'last_name' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:10'],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'sex' => ['nullable', Rule::enum(Sex::class)],
            'marital_status' => ['nullable', Rule::enum(MaritalStatus::class)],
            'hired_on' => ['nullable', 'date'],
            'weekly_hours' => ['nullable', 'integer', 'between:1,40'],
            'personal_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'regex:/^(09\d{9}|\+639\d{9})$/'],
            'address' => ['nullable', 'string', 'max:255'],
            ...$this->governmentIdRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid mobile number: 09XXXXXXXXX or +639XXXXXXXXX.',
            'birthdate.before' => 'The birthdate must be in the past.',
        ];
    }

    /**
     * The government ID numbers that were typed in, by type. A blank field means "leave as it is".
     *
     * @return array<string, string>
     */
    public function governmentIds(): array
    {
        return array_filter($this->only(['tin', 'gsis', 'sss', 'philhealth', 'pagibig', 'bir']), fn ($number): bool => filled($number));
    }

    /**
     * The record fields, without government IDs and blank strings turned into nulls.
     *
     * @return array<string, mixed>
     */
    public function personalAttributes(): array
    {
        return collect($this->safe()->except(['tin', 'gsis', 'sss', 'philhealth', 'pagibig', 'bir']))
            ->map(fn ($value) => is_string($value) ? (trim($value) === '' ? null : trim($value)) : $value)
            ->reject(fn ($value, string $key) => $key === 'weekly_hours' && $value === null)
            ->all();
    }
}
