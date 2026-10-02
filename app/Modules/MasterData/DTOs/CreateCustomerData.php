<?php

namespace App\Modules\MasterData\DTOs;

readonly class CreateCustomerData
{
    /** @param array<string, mixed> $attributes */
    public function __construct(public array $attributes) {}

    /** @param array<string, mixed> $attributes */
    public static function fromValidated(array $attributes): self
    {
        return new self($attributes);
    }
}
