<?php

namespace App\Modules\MasterData\Interfaces;

use App\Modules\MasterData\DTOs\CreateDriverData;
use App\Modules\MasterData\DTOs\UpdateDriverData;
use App\Modules\MasterData\Models\Driver;

interface DriverServiceInterface
{
    /** @return list<array<string, mixed>> */
    public function drivers(): array;

    /** @return array<string, mixed> */
    public function driver(Driver $driver): array;

    /** @return array<string, mixed> */
    public function create(CreateDriverData $data): array;

    /** @return array<string, mixed> */
    public function update(Driver $driver, UpdateDriverData $data): array;

    public function deactivate(Driver $driver): void;
}
