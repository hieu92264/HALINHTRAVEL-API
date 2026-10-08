<?php

namespace App\Modules\Contract\DTOs;

use App\Shared\Enums\WeekdayEnum;

readonly class ContractScheduleDayData
{
    public function __construct(
        public WeekdayEnum $weekday,
        public string $pickupTime,
        public ?string $returnTime,
        public ?string $shiftName,
    ) {}
}
