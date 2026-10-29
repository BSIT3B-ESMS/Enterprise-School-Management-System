<?php

namespace App\Enums;

enum DataRequestType: string
{
    case Access = 'access';
    case Correction = 'correction';
    case Blocking = 'blocking';
    case Erasure = 'erasure';
}
