<?php

namespace App\Shared\Enums;

use App\Shared\Enums\Concerns\EnumToArray;

enum PartnerTypeEnum: string
{
    use EnumToArray;

    case TRANSPORT_COMPANY = 'transport_company'; //công ty vận tải
    case VEHICLE_OWNER = 'vehicle_owner'; //chủ xe
    case GARAGE = 'garage';
    case FUEL_SUPPLIER = 'fuel_supplier'; //nhà cung cấp nhiên liệu
    case OTHER = 'other';
}
