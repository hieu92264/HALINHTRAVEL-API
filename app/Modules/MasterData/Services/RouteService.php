<?php

namespace App\Modules\MasterData\Services;

use App\Modules\MasterData\DTOs\CreateRouteData;
use App\Modules\MasterData\DTOs\UpdateRouteData;
use App\Modules\MasterData\Interfaces\RouteServiceInterface;
use App\Modules\MasterData\Models\Route;
use App\Modules\MasterData\Models\RouteVehicleTypeRate;
use App\Modules\MasterData\Models\VehicleType;
use Illuminate\Support\Facades\DB;

class RouteService implements RouteServiceInterface
{
    public function routes(): array
    {
        return Route::query()
            ->with(['customer:id,name', 'vehicleTypeRates.vehicleType:id,code,name'])
            ->orderBy('id')
            ->get()
            ->map(fn (Route $route): array => $this->route($route))
            ->all();
    }

    public function route(Route $route): array
    {
        $route->loadMissing(['customer:id,name', 'vehicleTypeRates.vehicleType:id,code,name']);

        return [
            'id' => $route->id,
            'code' => $route->code,
            'customer_id' => $route->customer_id,
            'customer_name' => $route->customer?->name,
            'name' => $route->name,
            'shift_name' => $route->shift_name,
            'pickup_location' => $route->pickup_location,
            'dropoff_location' => $route->dropoff_location,
            'default_pickup_time' => $route->default_pickup_time === null ? null : substr((string) $route->default_pickup_time, 0, 5),
            'default_return_time' => $route->default_return_time === null ? null : substr((string) $route->default_return_time, 0, 5),
            'estimated_distance_km' => $route->estimated_distance_km,
            'rates' => $route->vehicleTypeRates->sortBy(fn (RouteVehicleTypeRate $rate) => $rate->vehicleType?->code)->values()->map(fn (RouteVehicleTypeRate $rate) => [
                'vehicle_type_id' => $rate->vehicle_type_id,
                'vehicle_type_code' => $rate->vehicleType?->code,
                'vehicle_type_name' => $rate->vehicleType?->name,
                'freight_amount' => $rate->freight_amount,
                'driver_wage' => $rate->driver_wage,
            ])->all(),
            'is_active' => $route->is_active,
            'user_name_created' => $route->user_name_created,
            'user_name_updated' => $route->user_name_updated,
            'created_at' => $route->created_at?->toISOString(),
            'updated_at' => $route->updated_at?->toISOString(),
        ];
    }

    /** @throws \Throwable */
    public function create(CreateRouteData $data): array
    {
        return DB::transaction(function () use ($data): array {
            $this->ensureTimesAreDistinct($data->defaultPickupTime, $data->defaultReturnTime);

            $route = Route::create([
                ...$data->toArray(),
                'code' => $this->nextCode(),
            ]);
            $this->syncRates($route, $data->rates);

            return $this->route($route);
        });
    }

    public function update(Route $route, UpdateRouteData $data): array
    {
        $this->normalizeLegacyEmptyDistance($route);
        $changes = $data->toArray();

        $this->ensureTimesAreDistinct(
            $changes['default_pickup_time'] ?? $route->default_pickup_time,
            $changes['default_return_time'] ?? $route->default_return_time,
        );

        if (array_key_exists('is_active', $changes)) {
            $route->forceFill(['is_active' => $changes['is_active']]);
            unset($changes['is_active']);
        }

        $route->fill($changes)->save();
        if ($data->rates !== null) {
            $this->syncRates($route, $data->rates);
        }

        return $this->route($route->fresh());
    }

    public function deactivate(Route $route): void
    {
        $route->forceFill(['is_active' => false])->save();
    }

    private function nextCode(): string
    {
        $highestSequence = Route::query()
            ->where('code', 'like', 'TUYEN%')
            ->lockForUpdate()
            ->pluck('code')
            ->reduce(function (int $highestSequence, string $code): int {
                if (preg_match('/^TUYEN(\d+)$/', $code, $matches) !== 1) {
                    return $highestSequence;
                }

                return max($highestSequence, (int) $matches[1]);
            }, 0);

        return 'TUYEN'.str_pad((string) ($highestSequence + 1), 3, '0', STR_PAD_LEFT);
    }

    private function ensureTimesAreDistinct(?string $pickupTime, ?string $returnTime): void
    {
        if ($pickupTime === null || $returnTime === null) {
            return;
        }

        if (substr($pickupTime, 0, 5) === substr($returnTime, 0, 5)) {
            abort(422, 'Giờ về mặc định phải khác giờ đón mặc định.');
        }
    }

    /**
     * Earlier imports stored an optional DECIMAL value as an empty string.
     * Laravel 12 throws while comparing that raw value during save, so retain
     * the historical record but normalize this absent value in memory.
     */
    private function normalizeLegacyEmptyDistance(Route $route): void
    {
        $attributes = $route->getAttributes();
        if (($attributes['estimated_distance_km'] ?? null) !== '') {
            return;
        }

        Route::query()->whereKey($route->id)->update(['estimated_distance_km' => null]);
        $attributes['estimated_distance_km'] = null;
        $route->setRawAttributes($attributes, true);
    }

    /** @param list<array{vehicle_type_id:int,freight_amount:string|float|int,driver_wage:string|float|int}> $rates */
    private function syncRates(Route $route, array $rates): void
    {
        $activeTypes = VehicleType::query()->active()->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $provided = collect($rates)->pluck('vehicle_type_id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        if ($provided !== $activeTypes) {
            abort(422, 'Bảng giá phải có đúng một dòng cho mỗi loại xe đang hoạt động.');
        }
        $route->vehicleTypeRates()->delete();
        $route->vehicleTypeRates()->createMany($rates);
    }
}
