<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * NFR-06: CAPTCHA on registration. CAPTCHA_DRIVER=none (default) skips
 * it; 'turnstile' (Cloudflare) or 'recaptcha' (Google) check the widget's
 * token with the provider. A provider outage fails closed: registration is
 * refused and the applicant is asked to try again.
 */
class CaptchaVerifier
{
    private const ENDPOINTS = [
        'turnstile' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
        'recaptcha' => 'https://www.google.com/recaptcha/api/siteverify',
    ];

    public static function enabled(): bool
    {
        return isset(self::ENDPOINTS[config('admission.captcha.driver')]) && config('admission.captcha.site_key');
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
            return (bool) Http::asForm()->timeout(8)->post(self::ENDPOINTS[config('admission.captcha.driver')], [
                'secret' => config('admission.captcha.secret'),
                'response' => $token,
                'remoteip' => $ip,
            ])->json('success');
        } catch (Throwable) {
            return false;
        }
    }
}
