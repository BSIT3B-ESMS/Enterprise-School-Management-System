<?php

namespace App\Models;

use App\Enums\MaritalStatus;
use App\Enums\Sex;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What an applicant fills in once: personal and contact information. Education, work history, resume, and portfolio belong to
 * each application. The login email is the applicant's personal email (users.email).
 */
#[Fillable(['user_id', 'first_name', 'middle_name', 'last_name', 'suffix', 'birthdate', 'sex', 'marital_status', 'phone', 'address'])]
class ApplicantProfile extends Model
{
    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'sex' => Sex::class,
            'marital_status' => MaritalStatus::class,
        ];
    }

    /**
     * @return Attribute<string, never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => implode(' ', array_filter([$this->first_name, $this->middle_name, $this->last_name, $this->suffix])));
    }

    /**
     * Whether the one-time profile is complete enough to apply: birthdate, sex, civil status, phone, and address.
     */
    public function isComplete(): bool
    {
        return filled($this->birthdate) && filled($this->sex) && filled($this->marital_status) && filled($this->phone) && filled($this->address);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
