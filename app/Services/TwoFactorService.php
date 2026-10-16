<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\TwoFactorCode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Second login step (decision T4): a 6-digit code from an authenticator app
 * (staff) or sent by email (applicants). Login returns a short-lived
 * challenge token; the client trades challenge + code for the JWT.
 */
class TwoFactorService
{
    private const TTL_MINUTES = 10;

    private const MAX_ATTEMPTS = 5;

    public function __construct(private Google2FA $google2fa) {}

    public function required(User $user): bool
    {
        if ($user->hasTwoFactor()) {
            return true;
        }

        return $user->hasRole('applicant') && config('admission.applicant_email_2fa');
    }

    public function method(User $user): string
    {
        return $user->two_factor_method ?? $user->primaryRole()?->twoFactorMethod() ?? 'email';
    }

    /** Start a challenge; for email 2FA this also sends the code. */
    public function challenge(User $user): string
    {
        $token = Str::random(64);
        $entry = ['user_id' => $user->id, 'attempts' => 0, 'code' => null];

        if ($this->method($user) === 'email') {
            $code = (string) random_int(100000, 999999);
            $entry['code'] = Hash::make($code);
            $user->notify(new TwoFactorCode($code, self::TTL_MINUTES));
        }

        Cache::put($this->key($token), $entry, now()->addMinutes(self::TTL_MINUTES));

        return $token;
    }

    /** Returns the user when the code is right, null otherwise. */
    public function verify(string $token, string $code): ?User
    {
        $entry = Cache::get($this->key($token));
        if (! $entry || $entry['attempts'] >= self::MAX_ATTEMPTS) {
            return null;
        }

        $user = User::find($entry['user_id']);
        $ok = $user && ($entry['code']
            ? Hash::check($code, $entry['code'])
            : $this->google2fa->verifyKey((string) $user->two_factor_secret, $code));

        if (! $ok) {
            $entry['attempts']++;
            Cache::put($this->key($token), $entry, now()->addMinutes(self::TTL_MINUTES));

            return null;
        }

        Cache::forget($this->key($token));

        return $user;
    }

    /** Enrol a staff member in authenticator-app 2FA (artisan, no UI yet). */
    public function enableTotp(User $user): string
    {
        $secret = $this->google2fa->generateSecretKey();
        $user->forceFill([
            'two_factor_method' => 'totp',
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $this->google2fa->getQRCodeUrl(config('app.name'), $user->email, $secret);
    }

    public function enableEmail(User $user): void
    {
        $user->forceFill([
            'two_factor_method' => 'email',
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => now(),
        ])->save();
    }

    public function disable(User $user): void
    {
        $user->forceFill(['two_factor_method' => null, 'two_factor_secret' => null, 'two_factor_confirmed_at' => null])->save();
    }

    private function key(string $token): string
    {
        return '2fa:'.hash('sha256', $token);
    }
}
