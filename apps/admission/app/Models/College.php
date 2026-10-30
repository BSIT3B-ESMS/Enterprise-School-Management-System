<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A college and its dean, who sees interviews for its programs only (FR-17, §10.2). */
#[Fillable(['code', 'name', 'dean_user_id', 'interview_questions'])]
class College extends Model
{
    public const DEFAULT_QUESTIONS = [
        'Tell me about yourself.',
        'Why do you want to study in the PLP?',
        'Aside from academics, what can you offer?',
    ];

    protected function casts(): array
    {
        return ['interview_questions' => 'array'];
    }

    /** The dean's questions, or the defaults until the dean edits them. */
    public function questions(): array
    {
        return $this->interview_questions ?: self::DEFAULT_QUESTIONS;
    }

    public function dean(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dean_user_id');
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }
}
