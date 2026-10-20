<?php

namespace App\Enums;

enum AuditAction: string
{
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
    case Login = 'login';
    case Logout = 'logout';
    case LoginFailed = 'login_failed';
    case Export = 'export';
    case Reveal = 'reveal';
}
