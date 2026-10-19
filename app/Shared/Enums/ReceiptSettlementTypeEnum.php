<?php

namespace App\Shared\Enums;

use App\Shared\Enums\Concerns\EnumToArray;

enum ReceiptSettlementTypeEnum: string
{
    use EnumToArray;

    case STANDARD = 'standard';
    case DRIVER_DIRECT_COLLECTION = 'driver_direct_collection';
}
