<?php

namespace App\Modules\Auth\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Staff = 'staff';
}
