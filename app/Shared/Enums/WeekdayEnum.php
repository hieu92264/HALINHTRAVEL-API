<?php

namespace App\Shared\Enums;

use App\Shared\Enums\Concerns\EnumToArray;

enum WeekdayEnum: string
{
    use EnumToArray;

    case MONDAY = 'Mon';
    case TUESDAY = 'Tue';
    case WEDNESDAY = 'Wed';
    case THURSDAY = 'Thu';
    case FRIDAY = 'Fri';
    case SATURDAY = 'Sat';
    case SUNDAY = 'Sun';
}
