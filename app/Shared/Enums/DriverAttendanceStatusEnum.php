<?php

namespace App\Shared\Enums;

use App\Shared\Enums\Concerns\EnumToArray;

enum DriverAttendanceStatusEnum: string
{
    use EnumToArray;

    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PAYROLL_LOCKED = 'payroll_locked';
}
