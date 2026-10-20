<?php

namespace App\Models;

use App\Enums\EmploymentType;
use App\Enums\OfferKind;
use App\Enums\OfferStatus;
use Database\Factories\OfferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An offer of employment: a job offer to an applicant, a contract offer to an employee whose contract is ending, or a change
 * of assignment. It holds the summary of the position and the terms as they were when issued. Accepting with a typed full name
 * is the signature; it is then final.
 */
#[Fillable([
    'kind', 'application_id', 'employee_id', 'issued_by', 'employment_type', 'weekly_hours', 'job_title_id', 'college_id', 'office_id',
    'start_date', 'end_date', 'summary', 'terms', 'benefit_ids', 'reason', 'status', 'responded_at', 'response_note', 'signed_name',
    'signed_ip', 'applied_at',
])]
class Offer extends Model
{
    /** @use HasFactory<OfferFactory> */
    use HasFactory;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'kind' => OfferKind::class,
            'status' => OfferStatus::class,
            'employment_type' => EmploymentType::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'summary' => 'array',
            'benefit_ids' => 'array',
            'responded_at' => 'datetime',
            'applied_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Offer>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', OfferStatus::Pending);
    }

    public function isPending(): bool
    {
        return $this->status === OfferStatus::Pending;
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * @return BelongsTo<JobTitle, $this>
     */
    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }

    /**
     * @return BelongsTo<College, $this>
     */
    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    /**
     * @return BelongsTo<Office, $this>
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
