<?php

namespace App\Http\Requests\Auth;

use App\Services\Security\CaptchaVerifier;
use App\Support\ApplicantForm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/** Fields of the applicant portal's "Create account" form (#signup-form). */
class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100', 'regex:'.ApplicantForm::NAME],
            'last_name' => ['required', 'string', 'max:100', 'regex:'.ApplicantForm::NAME],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'regex:'.ApplicantForm::PH_MOBILE],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'terms' => ['accepted'],
            // NFR-06: only checked when a CAPTCHA provider is configured
            'captcha' => CaptchaVerifier::rule(),
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'An account with this email already exists. Sign in instead.',
            'phone.regex' => 'Use a PH mobile number, e.g. +63 917 123 4567.',
            'first_name.regex' => ApplicantForm::NAME_MESSAGE,
            'last_name.regex' => ApplicantForm::NAME_MESSAGE,
        ];
    }
}
