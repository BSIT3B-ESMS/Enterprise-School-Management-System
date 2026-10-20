<?php

namespace App\Services;

/**
 * Weekly working hours that mean full-time.
 */
final class EmployeeHours
{
    public const FULL = 40;

    /**
     * Weekly hours given to a part-time employee when HR does not say otherwise.
     */
    public const PART_TIME_DEFAULT = 20;
}
