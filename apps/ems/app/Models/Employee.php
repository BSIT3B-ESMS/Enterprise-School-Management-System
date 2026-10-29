<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\JobTitleCategory;
use App\Enums\MaritalStatus;
use App\Enums\Sex;
use Carbon\CarbonInterface;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The master record of a PLP faculty or staff member. A person belongs to a college (teaching), an office
 * (non-teaching), or both. Government ID numbers are write-only: they are stored encrypted, shown only as their last
 * four digits, and revealed only through an audited action.
 */
#[Fillable([
    'last_name', 'first_name', 'middle_name', 'suffix', 'birthdate', 'sex', 'marital_status', 'employment_type', 'weekly_hours', 'hired_on', 'contract_ends_on',
    'college_id', 'office_id', 'job_title_id', 'official_email', 'personal_email', 'phone', 'address',
    'status', 'status_reason', 'status_effective_on', 'retention_review_at',
])]
#[Hidden(['government_ids'])]
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    /**
     * The government ID numbers an employee record can hold.
     *
     * @var list<string>
     */
    public const GOVERNMENT_ID_KEYS = ['tin', 'gsis', 'sss', 'philhealth', 'pagibig', 'bir'];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'hired_on' => 'date',
            'contract_ends_on' => 'date',
            'status_effective_on' => 'date',
            'retention_review_at' => 'date',
            'sex' => Sex::class,
            'marital_status' => MaritalStatus::class,
            'employment_type' => EmploymentType::class,
            'weekly_hours' => 'integer',
            'status' => EmployeeStatus::class,
            'government_ids' => 'encrypted:array',
            'government_id_hints' => 'array',
        ];
    }

    /**
     * Employees are addressed by their employee number (for example 26-00001) in URLs.
     */
    public function getRouteKeyName(): string
    {
        return 'employee_no';
    }

    /**
     * "Last, First Middle" is how HR lists people; the full name reads in natural order.
     *
     * @return Attribute<string, never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => implode(' ', array_filter([$this->first_name, $this->middle_name, $this->last_name, $this->suffix])));
    }

    /**
     * The college and office an employee ends up in. A job title belongs to one of them; the other comes from the
     * optional extra assignment (a teacher based in an office, or office staff who also teach).
     *
     * @return array{college_id: ?int, office_id: ?int}
     */
    public static function unitsFor(JobTitle $title, ?int $extraCollegeId = null, ?int $extraOfficeId = null): array
    {
        return [
            'college_id' => $title->college_id ?? $extraCollegeId,
            'office_id' => $title->office_id ?? $extraOfficeId,
        ];
    }

    /**
     * The next employee number for someone hired on a date: the last two digits of the hire year, a dash, and a five-digit
     * counter that starts at 00001 each year (for example 26-00001, then 26-00002).
     */
    public static function nextEmployeeNo(CarbonInterface $hiredOn): string
    {
        $year = $hiredOn->format('y');
        $highest = static::query()->where('employee_no', 'like', "{$year}-%")->max(DB::raw('CAST(SUBSTRING(employee_no, 4) AS UNSIGNED)'));

        return $year.'-'.str_pad((string) (((int) $highest) + 1), 5, '0', STR_PAD_LEFT);
    }

    /**
     * The staff login/official email: surname_givenname@plpasig.edu.ph, lowercase with no spaces or punctuation.
     * A number is added after the given name when the address is already taken.
     */
    public static function officialEmailFor(string $lastName, string $firstName): string
    {
        $clean = fn (string $value): string => preg_replace('/[^a-z]/', '', mb_strtolower(Str::ascii($value)));
        $base = $clean($lastName).'_'.$clean($firstName);
        $email = "{$base}@plpasig.edu.ph";
        $suffix = 1;

        while (static::query()->where('official_email', $email)->exists() || User::query()->where('email', $email)->exists()) {
            $suffix++;
            $email = "{$base}{$suffix}@plpasig.edu.ph";
        }

        return $email;
    }

    /**
     * Stores government ID numbers (only the keys given and not blank), encrypted, together with the last four digits
     * that the UI is allowed to show. Existing numbers for keys not given are kept.
     *
     * @param  array<string, string|null>  $numbers
     */
    public function setGovernmentIds(array $numbers): void
    {
        $given = array_filter(Arr::only($numbers, self::GOVERNMENT_ID_KEYS), fn ($number): bool => filled($number));
        $merged = [...($this->government_ids ?? []), ...$given];

        $this->government_ids = $merged;
        $this->government_id_hints = array_map(
            fn (string $number): string => mb_substr(preg_replace('/\D/', '', $number), -4),
            $merged,
        );
    }

    /**
     * The last four digits of each stored ID number, keyed by ID type. This is all the UI shows by default.
     *
     * @return array<string, string>
     */
    public function maskedGovernmentIds(): array
    {
        return $this->government_id_hints ?? [];
    }

    /**
     * The reference base pay (Step 1 of the salary grade of the employee's job title) on a date (today by default).
     */
    public function baseRate(?CarbonInterface $on = null): ?RateTier
    {
        return RateTier::currentFor($this->jobTitle->salary_grade, $on);
    }

    /**
     * People whose name, employee number, or official email contains the term.
     *
     * @param  Builder<Employee>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $query->where(function (Builder $match) use ($term): void {
            $match->where('last_name', 'like', "%{$term}%")
                ->orWhere('first_name', 'like', "%{$term}%")
                ->orWhere('middle_name', 'like', "%{$term}%")
                ->orWhere('employee_no', 'like', "%{$term}%")
                ->orWhere('official_email', 'like', "%{$term}%")
                ->orWhereRaw("concat(first_name, ' ', last_name) like ?", ["%{$term}%"]);
        });
    }

    /**
     * The Employees list and its report: the tab (faculty or non-teaching), the search term, and the college, office, job
     * title, and employment type filters, then the sort. Only the keys present in $filters are applied.
     *
     * @param  Builder<Employee>  $query
     * @param  array<string, mixed>  $filters
     */
    public function scopeListed(Builder $query, array $filters): void
    {
        $query
            ->when(($filters['type'] ?? 'faculty') === 'faculty', fn (Builder $q) => $q->faculty(), fn (Builder $q) => $q->nonTeaching())
            ->when($filters['q'] ?? null, fn (Builder $q, string $term) => $q->search($term))
            ->when($filters['college'] ?? null, fn (Builder $q, $college) => $q->where('college_id', $college))
            ->when($filters['office'] ?? null, fn (Builder $q, $office) => $q->where('office_id', $office))
            ->when($filters['job_title'] ?? null, fn (Builder $q, $jobTitle) => $q->whereHas('jobTitle', fn (Builder $titles) => $titles->where('title', $jobTitle)))
            ->when($filters['employment_type'] ?? null, fn (Builder $q, $type) => $q->where('employment_type', $type));

        $direction = ($filters['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $columns = ['name' => ['last_name', 'first_name'], 'number' => ['employee_no'], 'hired' => ['hired_on']][$filters['sort'] ?? 'name'] ?? ['last_name', 'first_name'];

        foreach ($columns as $column) {
            $query->orderBy($column, $direction);
        }
    }

    /**
     * @param  Builder<Employee>  $query
     */
    public function scopeFaculty(Builder $query): void
    {
        $query->whereHas('jobTitle', fn (Builder $titles) => $titles->where('category', JobTitleCategory::Faculty));
    }

    /**
     * @param  Builder<Employee>  $query
     */
    public function scopeNonTeaching(Builder $query): void
    {
        $query->whereHas('jobTitle', fn (Builder $titles) => $titles->where('category', JobTitleCategory::NonTeaching));
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

    /**
     * @return BelongsTo<JobTitle, $this>
     */
    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }

    /**
     * @return HasMany<EmergencyContact, $this>
     */
    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmergencyContact::class);
    }

    /**
     * @return HasMany<CollegeTransfer, $this>
     */
    public function transfers(): HasMany
    {
        return $this->hasMany(CollegeTransfer::class);
    }

    /**
     * @return HasMany<LeaveRequest, $this>
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Whether approved leave covers today. This is what "On Leave" means everywhere in the system.
     */
    public function isOnLeaveToday(): bool
    {
        return $this->leaveRequests()->covering()->exists();
    }

    /**
     * Hours a full-time employee works a week; part-time staff earn leave credits in proportion to their own hours.
     */
    public const FULL_TIME_HOURS = 40;

    /**
     * Whether the employee's login has an HR role. An HR account must stay full-time, because the leave rules key off
     * employment_type.
     */
    public function hasHrAccount(): bool
    {
        return $this->user?->role->isHr() ?? false;
    }

    /**
     * The share of full leave credits this employee earns: 1 for full-time, weekly hours over 40 for part-time.
     */
    public function leaveCreditShare(): float
    {
        if ($this->employment_type === EmploymentType::FullTime) {
            return 1.0;
        }

        return min(1.0, max(0, (int) $this->weekly_hours) / self::FULL_TIME_HOURS);
    }

    /**
     * The status other systems see: inactive when the record is not active, on_leave when approved leave covers today,
     * otherwise active. It is never stored. A query that already selected is_on_leave saves the extra lookup.
     */
    public function effectiveStatus(): string
    {
        if ($this->status !== EmployeeStatus::Active) {
            return 'inactive';
        }

        return ($this->getAttribute('is_on_leave') ?? $this->isOnLeaveToday()) ? 'on_leave' : 'active';
    }

    /**
     * Why an employee is no longer active (resigned, retired, or terminated), or null.
     */
    public function statusDetail(): ?string
    {
        return in_array($this->status, [EmployeeStatus::Resigned, EmployeeStatus::Retired, EmployeeStatus::Terminated], true) ? $this->status->value : null;
    }

    /**
     * @return BelongsToMany<Benefit, $this>
     */
    public function benefits(): BelongsToMany
    {
        return $this->belongsToMany(Benefit::class, 'employee_benefits')->withPivot(['granted_on', 'details'])->withTimestamps();
    }

    /**
     * @return HasMany<Offer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    /**
     * @return MorphMany<StoredDocument, $this>
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(StoredDocument::class, 'documentable');
    }

    /**
     * The contract end is near (within the number of days) or already past. Full-time staff have no end date.
     */
    public function contractEndsWithin(int $days): bool
    {
        return $this->contract_ends_on !== null && $this->contract_ends_on->lte(now()->addDays($days)->endOfDay());
    }

    /**
     * @return HasMany<DataRequest, $this>
     */
    public function dataRequests(): HasMany
    {
        return $this->hasMany(DataRequest::class);
    }

    /**
     * @return HasOne<User, $this>
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
