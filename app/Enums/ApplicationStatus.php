<?php

namespace App\Enums;

/**
 * Where an application stands. Applied, Demo Interview, and Offered are the open stages; Hired, Rejected (by HR), and
 * Cancelled (by the applicant) are final.
 */
enum ApplicationStatus: string
{
    case Applied = 'applied';
    case DemoInterview = 'demo_interview';
    case Offered = 'offered';
    case Hired = 'hired';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Applied => 'Applied',
            self::DemoInterview => 'Demo Interview',
            self::Offered => 'Offered',
            self::Hired => 'Hired',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Hired, self::Rejected, self::Cancelled], true);
    }

    /**
     * The CSS class that colors the status (the .stage-* rules in _dashboard.scss).
     */
    public function cssClass(): string
    {
        return 'stage-'.str_replace('_', '-', $this->value);
    }

    /**
     * The stages of the pipeline, in order, for the dashboard and Recruitment page.
     *
     * @return list<self>
     */
    public static function pipeline(): array
    {
        return [self::Applied, self::DemoInterview, self::Offered, self::Hired, self::Rejected, self::Cancelled];
    }
}
