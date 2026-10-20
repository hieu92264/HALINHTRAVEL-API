<?php

namespace App\Modules\MasterData\DTOs;

use App\Shared\Enums\ExpenseTypeEnum;
use App\Shared\Enums\ProfitCategoryEnum;

readonly class UpdateExpenseTypeData
{
    /** @param list<string> $provided */
    public function __construct(
        public ?string $code,
        public ?string $name,
        public ?ExpenseTypeEnum $scope,
        public ?ProfitCategoryEnum $profitCategory,
        public ?bool $is_active,
        public array $provided = [],
    ) {}

    /** @return array<string, bool|string|null> */
    public function toArray(): array
    {
        $data = [
            'code' => $this->code,
            'name' => $this->name,
            'scope' => $this->scope?->value,
            'profit_category' => $this->profitCategory?->value,
            'is_active' => $this->is_active,
        ];

        return array_intersect_key($data, array_flip($this->provided));
    }
}
