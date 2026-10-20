<?php

namespace App\Enums;

enum LeaveStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Whether the request can still change: only a pending one can be decided or cancelled. Approved, rejected, and
     * cancelled requests are final.
     */
    public function isFinal(): bool
    {
        return $this !== self::Pending;
    }
}
