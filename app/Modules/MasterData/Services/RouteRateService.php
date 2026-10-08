<?php

namespace App\Modules\MasterData\Services;

use App\Modules\MasterData\DTOs\CreateRouteRateData;
use App\Modules\MasterData\DTOs\UpdateRouteRateData;
use App\Modules\MasterData\Interfaces\RouteRateServiceInterface;
use App\Modules\MasterData\Models\RouteRate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class RouteRateService implements RouteRateServiceInterface
{
    public function routeRates(): array
    {
        return RouteRate::query()
            ->with(['route:id,name', 'vehicleType:id,name'])
            ->orderBy('route_id')
            ->orderBy('vehicle_type_id')
            ->orderByDesc('effective_from')
            ->get()
            ->map(fn (RouteRate $routeRate): array => $this->routeRate($routeRate))
            ->all();
    }

    public function routeRate(RouteRate $routeRate): array
    {
        $routeRate->loadMissing(['route:id,name', 'vehicleType:id,name']);

        return [
            'id' => $routeRate->id,
            'route_id' => $routeRate->route_id,
            'route_name' => $routeRate->route?->name,
            'vehicle_type_id' => $routeRate->vehicle_type_id,
            'vehicle_type_name' => $routeRate->vehicleType?->name,
            'customer_price' => $routeRate->customer_price,
            'driver_wage' => $routeRate->driver_wage,
            'effective_from' => $routeRate->effective_from?->toDateString(),
            'effective_to' => $routeRate->effective_to?->toDateString(),
            'is_active' => $routeRate->is_active,
            'user_name_created' => $routeRate->user_name_created,
            'user_name_updated' => $routeRate->user_name_updated,
            'created_at' => $routeRate->created_at?->toISOString(),
            'updated_at' => $routeRate->updated_at?->toISOString(),
        ];
    }

    public function lookup(int $routeId, int $vehicleTypeId, string $atDate): array
    {
        $routeRate = RouteRate::query()
            ->active()
            ->where('route_id', $routeId)
            ->where('vehicle_type_id', $vehicleTypeId)
            ->whereDate('effective_from', '<=', $atDate)
            ->where(function (Builder $query) use ($atDate): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $atDate);
            })
            ->orderByDesc('effective_from')
            ->firstOrFail();

        return $this->routeRate($routeRate);
    }

    public function create(CreateRouteRateData $data): array
    {
        $attributes = $data->toArray();
        $this->ensurePeriodDoesNotOverlap($attributes);

        return $this->routeRate(RouteRate::create($attributes));
    }

    public function update(RouteRate $routeRate, UpdateRouteRateData $data): array
    {
        $changes = $data->toArray();
        $attributes = [
            'route_id' => $changes['route_id'] ?? $routeRate->route_id,
            'vehicle_type_id' => $changes['vehicle_type_id'] ?? $routeRate->vehicle_type_id,
            'effective_from' => $changes['effective_from'] ?? $routeRate->effective_from?->toDateString(),
            'effective_to' => array_key_exists('effective_to', $changes)
                ? $changes['effective_to']
                : $routeRate->effective_to?->toDateString(),
        ];

        $this->ensurePeriodDoesNotOverlap($attributes, $routeRate->id);

        if (array_key_exists('is_active', $changes)) {
            $routeRate->forceFill(['is_active' => $changes['is_active']]);
            unset($changes['is_active']);
        }

        $routeRate->fill($changes)->save();

        return $this->routeRate($routeRate->fresh());
    }

    public function deactivate(RouteRate $routeRate): void
    {
        $routeRate->forceFill(['is_active' => false])->save();
    }

    /** @param array{route_id: int, vehicle_type_id: int, effective_from: string, effective_to: ?string} $attributes */
    private function ensurePeriodDoesNotOverlap(array $attributes, ?int $excludingId = null): void
    {
        $query = RouteRate::query()
            ->active()
            ->where('route_id', $attributes['route_id'])
            ->where('vehicle_type_id', $attributes['vehicle_type_id'])
            ->whereDate('effective_from', '<=', $attributes['effective_to'] ?? '9999-12-31')
            ->where(function (Builder $query) use ($attributes): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $attributes['effective_from']);
            });

        if ($excludingId !== null) {
            $query->whereKeyNot($excludingId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'effective_from' => 'Khoảng thời gian hiệu lực trùng với một mức giá đang hoạt động của tuyến và loại xe này.',
            ]);
        }
    }
}
