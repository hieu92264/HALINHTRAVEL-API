<?php

namespace App\Modules\Contract\DTOs;

readonly class UpdateContractScheduleRuleData
{
    /** @param array<string, int|string|null> $values */
    public function __construct(public array $values) {}
}
