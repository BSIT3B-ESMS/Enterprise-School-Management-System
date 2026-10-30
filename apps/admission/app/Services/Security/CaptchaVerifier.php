<?php

namespace App\Services\Security;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * NFR-06: CAPTCHA on the applicant's sign-up, sign-in and forgot-password
 * forms. CAPTCHA_DRIVER=none (default) skips it; 'turnstile'
 * (Cloudflare) or 'recaptcha' (Google v2 checkbox) check the widget's token
 * with the provider. A provider outage fails closed: the request is refused
 * and the applicant is asked to try again.
 */
class CaptchaVerifier
{
    /** driver => [verify endpoint, widget CSS class, widget script] */
    private const PROVIDERS = [
        'turnstile' => ['https://challenges.cloudflare.com/turnstile/v0/siteverify', 'cf-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js'],
        'recaptcha' => ['https://www.google.com/recaptcha/api/siteverify', 'g-recaptcha', 'https://www.google.com/recaptcha/api.js'],
    ];

    public static function enabled(): bool
    {
        return isset(self::PROVIDERS[config('admission.captcha.driver')]) && config('admission.captcha.site_key');
    }

    public static function widgetClass(): string
    {
        return self::PROVIDERS[config('admission.captcha.driver')][1];
    }

    public static function script(): string
    {
        return self::PROVIDERS[config('admission.captcha.driver')][2];
    }

    /** Validation rules for the form's `captcha` field (checked only while a provider is configured). */
    public static function rule(): array
    {
        return [new class implements ValidationRule
        {
            // Runs when the field is missing too, so an unticked box gets the same message
            public bool $implicit = true;

            public function validate(string $attribute, mixed $value, Closure $fail): void
            {
                if (! app(CaptchaVerifier::class)->verify(is_string($value) ? $value : null, request()->ip())) {
                    $fail('Please complete the "I am human" check and try again.');
                }
            }
        }];
    }

    public function verify(?string $token, ?string $ip): bool
    {
        if (! self::enabled()) {
            return true;
        }
        if (! $token) {
            return false;
        }
        try {
            return (bool) Http::asForm()->timeout(8)->post(self::PROVIDERS[config('admission.captcha.driver')][0], [
                'secret' => config('admission.captcha.secret'),
                'response' => $token,
                'remoteip' => $ip,
            ])->json('success');
        } catch (Throwable) {
            return false;
        }
    }
}
