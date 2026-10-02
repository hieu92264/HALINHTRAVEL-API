<?php

namespace App\Modules\MasterData\DTOs;

use App\Shared\Enums\OwnershipTypeEnum;

readonly class UpdateDriverData
{
    /** @param list<string> $provided */
    public function __construct(
        public ?string $userName,
        public ?int $partnerId,
        public ?OwnershipTypeEnum $type,
        public ?string $fullName,
        public ?string $phone,
        public ?string $cccd,
        public ?string $licenseNumber,
        public ?string $licenseClass,
        public ?string $licenseIssuedAt,
        public ?string $licenseExpiredAt,
        public ?string $baseSalary,
        public ?string $responsibilityAllowance,
        public ?string $joinedAt,
        public ?string $leftAt,
        public ?bool $isActive,
        public array $provided = [],
    ) {}

    /** @return array<string, bool|int|string|null> */
    public function toArray(): array
    {
        $data = [
            'user_name' => $this->userName,
            'partner_id' => $this->partnerId,
            'type' => $this->type?->value,
            'full_name' => $this->fullName,
            'phone' => $this->phone,
            'cccd' => $this->cccd,
            'license_number' => $this->licenseNumber,
            'license_class' => $this->licenseClass,
            'license_issued_at' => $this->licenseIssuedAt,
            'license_expired_at' => $this->licenseExpiredAt,
            'base_salary' => $this->baseSalary,
            'responsibility_allowance' => $this->responsibilityAllowance,
            'joined_at' => $this->joinedAt,
            'left_at' => $this->leftAt,
            'is_active' => $this->isActive,
        ];

        return array_intersect_key($data, array_flip($this->provided));
    }
}
