<?php

namespace App\Shared\Enums;

use App\Shared\Enums\Concerns\EnumToArray;

enum TripAssignmentTypeEnum: string
{
    use EnumToArray;

    case PRIMARY = 'PRIMARY';// tài xế chính
    case SUBSTITUTE = 'SUBSTITUTE'; // Thay thế
}
