<?php

namespace App\Http\Requests;

use App\Enums\MaritalStatus;
use App\Enums\Sex;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The fields of an employee record that HR Staff and the HR Head both enter: name, personal details, contact details,
 * and government ID numbers. Subclasses add what they need (the job assignment when creating).
 */
abstract class EmployeeRequest extends FormRequest
{
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
            'personal_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'regex:/^(09\d{9}|\+639\d{9})$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'tin' => ['nullable', $this->digits([9, 12], 'TIN must be 9 digits, or 12 with a branch code.')],
            'gsis' => ['nullable', $this->digits([11], 'GSIS number must be 11 digits.')],
            'sss' => ['nullable', $this->digits([10], 'SSS number must be 10 digits.')],
            'philhealth' => ['nullable', $this->digits([12], 'PhilHealth number must be 12 digits.')],
            'pagibig' => ['nullable', $this->digits([12], 'Pag-IBIG MID number must be 12 digits.')],
            'bir' => ['nullable', $this->digits([9, 10, 11, 12, 13], 'BIR number must be 9 to 13 digits.')],
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
            ->all();
    }

    /**
     * A number made of digits, dashes, and spaces with one of the allowed digit counts.
     *
     * @param  list<int>  $counts
     */
    private function digits(array $counts, string $message): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($counts, $message): void {
            if (! preg_match('/^[\d\s-]+$/', (string) $value) || ! in_array(strlen((string) preg_replace('/\D/', '', (string) $value)), $counts, true)) {
                $fail($message);
            }
        };
    }
}
