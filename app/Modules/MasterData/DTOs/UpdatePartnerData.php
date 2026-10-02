<?php

namespace App\Modules\MasterData\DTOs;

use App\Shared\Enums\PartnerTypeEnum;

readonly class UpdatePartnerData
{
    /** @param list<string> $provided */
    public function __construct(
        public ?PartnerTypeEnum $type,
        public ?string $name,
        public ?string $phone,
        public ?string $email,
        public ?string $cccd,
        public ?string $tax_code,
        public ?string $address,
        public ?string $bank_name,
        public ?string $bank_account,
        public ?string $opening_balance,
        public ?bool $is_active,
        public array $provided = [],
    ) {}

    /** @return array<string, string|bool|null> */
    public function toArray(): array
    {
        $data = [
            'type' => $this->type?->value,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'cccd' => $this->cccd,
            'tax_code' => $this->tax_code,
            'address' => $this->address,
            'bank_name' => $this->bank_name,
            'bank_account' => $this->bank_account,
            'opening_balance' => $this->opening_balance,
            'is_active' => $this->is_active,
        ];

        return array_intersect_key($data, array_flip($this->provided));
    }
}
