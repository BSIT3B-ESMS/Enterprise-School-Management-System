<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An applicant's interview (FR-17). The grade is Restricted (§8.3): hidden
 * from serialization and never in an applicant payload.
 */
#[Fillable(['application_id', 'interview_schedule_id', 'assigned_by', 'grade', 'outcome', 'referred_program_id', 'remark', 'recorded_by', 'recorded_at'])]
#[Hidden(['grade'])]
class InterviewAssignment extends Model
{
    public const PASSED = 'passed';

    public const FAILED = 'failed';

    /** Not accepted into this program, referred to another of the applicant's choices. */
    public const REFERRED = 'referred';

    protected function casts(): array
    {
        return [
            'grade' => 'integer',
            'recorded_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(InterviewSchedule::class, 'interview_schedule_id');
    }

    public function referredProgram(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'referred_program_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
