<?php

namespace App\Shared\Enums;

use App\Shared\Enums\Concerns\EnumToArray;

enum RentalServiceTypeEnum: string
{
    use EnumToArray;

    case FIXED = 'fixed';
    case TOURISM = 'tourism';
    case SCHOOL = 'school';
    case BUSINESS = 'business';

    /** @return list<string> */
    public static function operationalValues(): array
    {
        return [self::TOURISM->value, self::BUSINESS->value];
    }

    public function isOperational(): bool
    {
        return in_array($this->value, self::operationalValues(), true);
    }
}
