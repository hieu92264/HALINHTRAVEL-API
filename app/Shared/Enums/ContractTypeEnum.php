<?php

namespace App\Shared\Enums;

use App\Shared\Enums\Concerns\EnumToArray;

enum ContractTypeEnum: string
{
    use EnumToArray;

    case TRIP = 'trip'; // Hợp đồng theo chuyến
    case PRINCIPLE = 'principle'; // Hợp đồng nguyên tắc
}
