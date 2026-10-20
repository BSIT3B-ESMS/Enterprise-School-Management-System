<?php

namespace App\Enums;

enum EmployeeStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Resigned = 'resigned';
    case Retired = 'retired';
    case Terminated = 'terminated';
}
