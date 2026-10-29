<?php

namespace App\Services;

use App\Actions\Recruitment\RecruitmentException;
use Carbon\CarbonInterface;

/**
 * Interviews are held during working hours, 8:00 AM to 5:00 PM (config ems.working_hours).
 */
class WorkingHours
{
    /**
     * @throws RecruitmentException
     */
    public function assertWithin(CarbonInterface $start, CarbonInterface $end): void
    {
        $opens = $start->copy()->setTimeFromTimeString(config('ems.working_hours.start'));
        $closes = $start->copy()->setTimeFromTimeString(config('ems.working_hours.end'));

        if ($start->lt($opens) || $end->gt($closes) || ! $end->isSameDay($start)) {
            throw new RecruitmentException('Interviews are held between '.$opens->format('g:i A').' and '.$closes->format('g:i A').'. Choose a start and end inside those hours.');
        }
    }
}
