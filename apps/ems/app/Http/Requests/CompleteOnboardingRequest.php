<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\GovernmentIdRules;
use Illuminate\Foundation\Http\FormRequest;

class CompleteOnboardingRequest extends FormRequest
{
    use GovernmentIdRules;

    public function authorize(): bool
    {
        return $this->user()?->employee_id !== null;
    }

    /**
     * TIN, GSIS, PhilHealth, and Pag-IBIG are required; SSS and BIR may be left blank.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contact_name' => ['required', 'string', 'max:150'],
            'contact_relationship' => ['required', 'string', 'max:50'],
            'contact_phone' => ['required', 'regex:/^(09\d{9}|\+639\d{9})$/'],
            'contact_address' => ['nullable', 'string', 'max:255'],
            ...$this->governmentIdRules(['tin', 'gsis', 'philhealth', 'pagibig']),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['contact_phone.regex' => 'Enter a valid mobile number: 09XXXXXXXXX or +639XXXXXXXXX.'];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'contact_name' => 'emergency contact name',
            'contact_relationship' => 'relationship',
            'contact_phone' => 'emergency contact phone',
            'contact_address' => 'emergency contact address',
            'pagibig' => 'Pag-IBIG',
            'philhealth' => 'PhilHealth',
        ];
    }
}
