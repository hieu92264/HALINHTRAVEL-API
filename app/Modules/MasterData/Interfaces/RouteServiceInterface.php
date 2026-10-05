<?php

namespace App\Modules\MasterData\Interfaces;

use App\Modules\MasterData\DTOs\CreateRouteData;
use App\Modules\MasterData\DTOs\UpdateRouteData;
use App\Modules\MasterData\Models\Route;

interface RouteServiceInterface
{
    /** @return list<array<string, mixed>> */
    public function routes(): array;

    /** @return array<string, mixed> */
    public function route(Route $route): array;

    /** @return array<string, mixed> */
    public function create(CreateRouteData $data): array;

    /** @return array<string, mixed> */
    public function update(Route $route, UpdateRouteData $data): array;

    public function deactivate(Route $route): void;
}
