<?php

namespace App\Modules\Contract\Interfaces;

use App\Modules\Contract\DTOs\ContractScheduleDayData;
use App\Modules\Contract\DTOs\ContractScheduleRuleData;
use App\Modules\Contract\DTOs\GenerateTripSchedulesData;
use App\Modules\Contract\DTOs\UpdateContractScheduleRuleData;
use App\Modules\Contract\Models\Contract;
use App\Modules\Contract\Models\ContractScheduleRule;

interface ContractScheduleRuleServiceInterface
{
    public function getList(Contract $contract): array;

    public function getDetail(ContractScheduleRule $scheduleRule): array;

    public function store(Contract $contract, ContractScheduleRuleData $data): array;

    public function update(ContractScheduleRule $scheduleRule, UpdateContractScheduleRuleData $data): array;

    public function delete(ContractScheduleRule $scheduleRule): void;

    /** @param list<ContractScheduleDayData> $days */
    public function replaceDays(ContractScheduleRule $scheduleRule, array $days): array;

    public function generateTripSchedules(ContractScheduleRule $scheduleRule, GenerateTripSchedulesData $data): array;
}
