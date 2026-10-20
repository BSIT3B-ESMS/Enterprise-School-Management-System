<?php

namespace App\Actions\Recruitment;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The interview would overlap something, so the change was refused. The person scheduling sees the list and can choose
 * another time or a different interviewer who is free.
 */
class InterviewConflictException extends RecruitmentException
{
    /**
     * @param  list<string>  $conflicts
     * @param  Collection<int, User>  $freeInterviewers
     */
    public function __construct(public array $conflicts, public Collection $freeInterviewers)
    {
        parent::__construct('This time overlaps: '.implode(' ', $conflicts));
    }
}
