<?php

namespace App\Modules\MasterData\Interfaces;

use App\Modules\MasterData\DTOs\CreateRouteRateData;
use App\Modules\MasterData\DTOs\UpdateRouteRateData;
use App\Modules\MasterData\Models\RouteRate;

interface RouteRateServiceInterface
{
    /** @return list<array<string, mixed>> */
    public function routeRates(): array;

    /** @return array<string, mixed> */
    public function routeRate(RouteRate $routeRate): array;

    /** @return array<string, mixed> */
    public function lookup(int $routeId, int $vehicleTypeId, string $atDate): array;

    /** @return array<string, mixed> */
    public function create(CreateRouteRateData $data): array;

    /** @return array<string, mixed> */
    public function update(RouteRate $routeRate, UpdateRouteRateData $data): array;

    public function deactivate(RouteRate $routeRate): void;
}
