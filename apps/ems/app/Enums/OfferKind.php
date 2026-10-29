<?php

namespace App\Enums;

enum OfferKind: string
{
    case Hire = 'hire';
    case Renewal = 'renewal';
    case Change = 'change';

    public function label(): string
    {
        return match ($this) {
            self::Hire => 'Job offer',
            self::Renewal => 'Contract offer',
            self::Change => 'Change of assignment',
        };
    }
}
