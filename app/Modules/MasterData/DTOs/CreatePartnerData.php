<?php

namespace App\Modules\MasterData\DTOs;

use App\Shared\Enums\PartnerTypeEnum;

readonly class CreatePartnerData
{
    public function __construct(
        public PartnerTypeEnum $type,
        public string $name,
        public ?string $phone,
        public ?string $email,
        public ?string $cccd,
        public ?string $tax_code,
        public ?string $address,
        public ?string $bank_name,
        public ?string $bank_account,
        public string $opening_balance,
    ) {}

    /** @return array<string, string|null> */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'cccd' => $this->cccd,
            'tax_code' => $this->tax_code,
            'address' => $this->address,
            'bank_name' => $this->bank_name,
            'bank_account' => $this->bank_account,
            'opening_balance' => $this->opening_balance,
        ];
    }
}
