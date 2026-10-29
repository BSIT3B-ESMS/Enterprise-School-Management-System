<?php

namespace App\Enums;

enum DataRequestStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Declined = 'declined';
}
