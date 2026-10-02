<?php

namespace App\Modules\MasterData\DTOs;

use App\Shared\Enums\CustomerEnum;

readonly class UpdateCustomerData
{
    public function __construct(
        public ?CustomerEnum $type,
        public ?string $name,
        public ?string $phone,
        public ?string $email,
        public ?string $cccd,
        public ?string $tax_code,
        public ?string $address,
        public ?string $contact_name,
        public ?string $opening_balance,
        public ?bool $is_active,

        /** @var list<string> */
        public array $provided = [],
    ) {}

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
            'contact_name' => $this->contact_name,
            'opening_balance' => $this->opening_balance,
            'is_active' => $this->is_active,
        ];

        return array_intersect_key(
            $data,
            array_flip($this->provided)
        );
    }
}
