<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * An external system (Payroll, for example) allowed to read the API. Only a hash of its token is stored; the token
 * itself is shown once, when it is issued.
 */
#[Fillable(['name', 'abilities', 'is_active', 'agreement_ref'])]
#[Hidden(['token_hash'])]
class ApiConsumer extends Model
{
    /**
     * What a consumer can be allowed to read.
     *
     * @var list<string>
     */
    public const ABILITIES = ['employees:read', 'reference:read'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'is_active' => 'boolean',
            'token_issued_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<ApiAccessLog, $this>
     */
    public function accessLogs(): HasMany
    {
        return $this->hasMany(ApiAccessLog::class);
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * A new random token: ems_ followed by 48 characters.
     */
    public static function generateToken(): string
    {
        return 'ems_'.Str::random(48);
    }

    public function can(string $ability): bool
    {
        return in_array($ability, $this->abilities ?? [], true);
    }

    /**
     * Replaces the token with a new one and returns it. The old token stops working immediately.
     */
    public function issueToken(): string
    {
        $token = self::generateToken();

        $this->forceFill(['token_hash' => self::hashToken($token), 'token_issued_at' => now()])->save();

        return $token;
    }
}
