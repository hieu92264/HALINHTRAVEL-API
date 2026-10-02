<?php

namespace App\Modules\MasterData\DTOs;

use App\Shared\Enums\CustomerEnum;

readonly class CreateCustomerData
{
    public function __construct(
        public CustomerEnum $type,
        public string $name,
        public ?string $phone,
        public ?string $email,
        public ?string $cccd,
        public ?string $tax_code,
        public ?string $address,
        public ?string $contact_name,
        public string $opening_balance,
    ) {}

    /** @return array<string, CustomerEnum|string|null> */
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
            'contact_name' => $this->contact_name,
            'opening_balance' => $this->opening_balance,
        ];
    }
}
