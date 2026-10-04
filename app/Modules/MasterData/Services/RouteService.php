<?php

namespace App\Modules\MasterData\Services;

use App\Modules\MasterData\DTOs\CreateRouteData;
use App\Modules\MasterData\DTOs\UpdateRouteData;
use App\Modules\MasterData\Interfaces\RouteServiceInterface;
use App\Modules\MasterData\Models\Route;
use Illuminate\Support\Facades\DB;

class RouteService implements RouteServiceInterface
{
    public function routes(): array
    {
        return Route::query()
            ->orderBy('id')
            ->get()
            ->map(fn (Route $route): array => $this->route($route))
            ->all();
    }

    public function route(Route $route): array
    {
        return [
            'id' => $route->id,
            'code' => $route->code,
            'customer_id' => $route->customer_id,
            'name' => $route->name,
            'shift_name' => $route->shift_name,
            'pickup_location' => $route->pickup_location,
            'dropoff_location' => $route->dropoff_location,
            'default_pickup_time' => $route->default_pickup_time,
            'default_return_time' => $route->default_return_time,
            'estimated_distance_km' => $route->estimated_distance_km,
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
            $route = Route::create([
                ...$data->toArray(),
                'code' => $this->nextCode(),
            ]);

            return $this->route($route);
        });
    }

    public function update(Route $route, UpdateRouteData $data): array
    {
        $changes = $data->toArray();

        if (array_key_exists('is_active', $changes)) {
            $route->forceFill(['is_active' => $changes['is_active']]);
            unset($changes['is_active']);
        }

        $route->fill($changes)->save();

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
}
