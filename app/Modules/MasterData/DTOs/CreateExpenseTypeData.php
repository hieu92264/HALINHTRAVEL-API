<?php

namespace App\Modules\MasterData\DTOs;

use App\Shared\Enums\ExpenseTypeEnum;

readonly class CreateExpenseTypeData
{
    public function __construct(
        public string $code,
        public string $name,
        public ExpenseTypeEnum $scope,
    ) {}

    /** @return array<string, string> */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'scope' => $this->scope->value,
        ];
    }
}
