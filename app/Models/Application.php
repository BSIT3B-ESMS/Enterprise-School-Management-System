<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\InterviewStatus;
use App\Enums\OfferStatus;
use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * One application of one applicant to one position (a job title). It moves Applied, Demo Interview, Offered, Hired, or ends as
 * Rejected (by HR) or Cancelled (by the applicant). The resume (education, work history, and an uploaded file) and the portfolio
 * belong here, not to the applicant's profile.
 */
#[Fillable([
    'user_id', 'job_title_id', 'status', 'applied_on', 'education', 'work_history', 'portfolio_url', 'cover_note',
    'status_reason', 'status_changed_at', 'employee_id',
])]
class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'applied_on' => 'date',
            'education' => 'array',
            'work_history' => 'array',
            'status_changed_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Application>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', [ApplicationStatus::Applied, ApplicationStatus::DemoInterview, ApplicationStatus::Offered]);
    }

    /**
     * People whose name or position matches the term.
     *
     * @param  Builder<Application>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $query->where(function (Builder $match) use ($term): void {
            $match->whereHas('applicant.applicantProfile', fn (Builder $profiles) => $profiles
                ->where('first_name', 'like', "%{$term}%")->orWhere('last_name', 'like', "%{$term}%")->orWhere('middle_name', 'like', "%{$term}%"))
                ->orWhereHas('applicant', fn (Builder $users) => $users->where('email', 'like', "%{$term}%"))
                ->orWhereHas('jobTitle', fn (Builder $titles) => $titles->where('title', 'like', "%{$term}%"));
        });
    }

    /**
     * The interview that is still to happen (scheduled), if any.
     */
    public function activeInterview(): ?Interview
    {
        return $this->interviews->firstWhere('status', InterviewStatus::Scheduled);
    }

    public function pendingOffer(): ?Offer
    {
        return $this->offers->firstWhere('status', OfferStatus::Pending);
    }

    public function acceptedOffer(): ?Offer
    {
        return $this->offers->firstWhere('status', OfferStatus::Accepted);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<JobTitle, $this>
     */
    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return HasMany<Interview, $this>
     */
    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class)->orderBy('starts_at');
    }

    /**
     * @return HasMany<Offer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class)->latest('id');
    }

    /**
     * @return HasMany<ApplicationEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(ApplicationEvent::class)->orderBy('id');
    }

    /**
     * @return MorphMany<StoredDocument, $this>
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(StoredDocument::class, 'documentable');
    }

    /**
     * @return HasOne<StoredDocument, $this>
     */
    public function resume(): HasOne
    {
        return $this->hasOne(StoredDocument::class, 'documentable_id')->where('documentable_type', $this->getMorphClass())->where('kind', 'resume');
    }
}
