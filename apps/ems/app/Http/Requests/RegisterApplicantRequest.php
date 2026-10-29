<?php

namespace App\Http\Requests;

use App\Services\PersonalMail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterApplicantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * An applicant signs up with their personal email. A PLP address is refused: those are for staff and may belong to
     * someone else.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:10'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email', function (string $attribute, mixed $value, \Closure $fail): void {
                if (app(PersonalMail::class)->isOfficialAddress((string) $value)) {
                    $fail('Use your personal email address. PLP staff addresses cannot be used for an applicant account.');
                }
            }],
            'password' => ['required', 'confirmed', Password::defaults()],
            'terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'An account with this email already exists. Log in instead.',
            'terms.accepted' => 'You need to accept the Terms and Conditions to create an account.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }
}
