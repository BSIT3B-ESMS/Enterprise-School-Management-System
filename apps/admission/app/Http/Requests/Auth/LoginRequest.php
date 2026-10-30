<?php

namespace App\Http\Requests\Auth;

use App\Services\Security\CaptchaVerifier;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            // Which sign-in screen sent this: the applicant portal or the employee portal
            'portal' => ['required', 'in:applicant,office'],
            // NFR-06: only the applicant sign-in has the widget (staff use 2FA instead)
            'captcha' => $this->input('portal') === 'applicant' ? CaptchaVerifier::rule() : [],
        ];
    }
}
