<?php

namespace App\Enums;

enum MaritalStatus: string
{
    case Single = 'Single';
    case Married = 'Married';
    case Widowed = 'Widowed';
    case Separated = 'Separated';
}
