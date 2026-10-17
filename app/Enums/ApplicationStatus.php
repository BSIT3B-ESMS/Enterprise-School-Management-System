<?php

namespace App\Enums;

/**
 * Application status set (FR-05, decision D8). Values match the keys the
 * admission office UI already uses (ADM.STATUSES).
 *
 * "passed" is not treated as a final state anywhere: medical clearance and
 * enrollment come after it.
 */
enum ApplicationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Resubmit = 'resubmit';
    case ForExam = 'for_exam';
    case Passed = 'passed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Resubmit => 'Requires resubmission',
            self::ForExam => 'For exam',
            self::Passed => 'Passed',
            self::Failed => 'Did not pass',
        };
    }

    /** @return list<self> */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted],
            self::Submitted => [self::Resubmit, self::ForExam],
            self::Resubmit => [self::Submitted, self::ForExam],
            self::ForExam => [self::Passed, self::Failed],
            // A result can be corrected before release
            self::Passed => [self::Failed],
            self::Failed => [self::Passed],
        };
    }

    public function canMoveTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }

    public function isLocked(): bool
    {
        return $this !== self::Draft;
    }
}
