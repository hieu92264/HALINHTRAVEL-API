<?php

namespace App\Modules\MasterData\Services;

use App\Modules\MasterData\DTOs\CreateVehicleData;
use App\Modules\MasterData\DTOs\UpdateVehicleData;
use App\Modules\MasterData\Interfaces\VehicleServiceInterface;
use App\Modules\MasterData\Models\Vehicle;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\VehicleType;
use App\Shared\Enums\OwnershipTypeEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VehicleService implements VehicleServiceInterface
{
    public function vehicles(): array
    {
        return Vehicle::query()->with(['vehicleType:id,code,name', 'defaultDriver:id,full_name'])->where('ownership_type', OwnershipTypeEnum::COMPANY)->orderBy('id')->get()->map(fn (Vehicle $vehicle): array => $this->vehicle($vehicle))->all();
    }

    public function options(?string $ownershipType = null): array
    {
        return Vehicle::query()
            ->with(['vehicleType:id,code,name', 'defaultDriver:id,full_name'])
            ->active()
            ->where('ownership_type', OwnershipTypeEnum::COMPANY)
            ->orderBy('license_plate')
            ->get()
            ->map(fn (Vehicle $vehicle): array => $this->vehicle($vehicle))
            ->all();
    }

    public function vehicle(Vehicle $vehicle): array
    {
        $vehicle->loadMissing(['vehicleType:id,code,name', 'defaultDriver:id,full_name']);

        return [
            'id' => $vehicle->id,
            'code' => $vehicle->code,
            'license_plate' => $vehicle->license_plate,
            'vehicle_type_id' => $vehicle->vehicle_type_id,
            'vehicle_type_code' => $vehicle->vehicleType?->code,
            'vehicle_type_name' => $vehicle->vehicleType?->name,
            'default_driver_id' => $vehicle->default_driver_id,
            'default_driver_name' => $vehicle->defaultDriver?->full_name,
            'note' => $vehicle->note,
            'is_active' => $vehicle->is_active,
            'created_at' => $vehicle->created_at?->toISOString(),
            'updated_at' => $vehicle->updated_at?->toISOString(),
        ];
    }

    public function create(CreateVehicleData $data): array
    {
        $this->assertDriver($data->default_driver_id);
        $seatCount = $this->seatCountForVehicleType($data->vehicle_type_id);

        return DB::transaction(function () use ($data, $seatCount): array {
            $vehicle = Vehicle::create([...$data->toArray(), 'seat_count' => $seatCount, 'ownership_type' => OwnershipTypeEnum::COMPANY->value, 'partner_id' => null, 'code' => $this->nextCode()]);

            return $this->vehicle($vehicle);
        });
    }

    public function update(Vehicle $vehicle, UpdateVehicleData $data): array
    {
        $changes = $data->toArray();
        if (array_key_exists('vehicle_type_id', $changes)) {
            $changes['seat_count'] = $this->seatCountForVehicleType((int) $changes['vehicle_type_id']);
        }
        if (array_key_exists('default_driver_id', $changes)) {
            $this->assertDriver($changes['default_driver_id']);
        }
        if (array_key_exists('is_active', $changes)) {
            $vehicle->forceFill(['is_active' => $changes['is_active']]);
            unset($changes['is_active']);
        }
        $vehicle->fill($changes)->save();

        return $this->vehicle($vehicle->fresh());
    }

    public function deactivate(Vehicle $vehicle): void
    {
        $vehicle->forceFill(['is_active' => false])->save();
    }

    private function assertDriver(int $driverId): void
    {
        if (! Driver::query()->active()->whereKey($driverId)->where('type', OwnershipTypeEnum::COMPANY)->exists()) {
            throw ValidationException::withMessages(['default_driver_id' => 'Tài xế mặc định phải là tài xế công ty đang hoạt động.']);
        }
    }

    private function nextCode(): string
    {
        $highest = Vehicle::query()->where('code', 'like', 'VX%')->lockForUpdate()->pluck('code')->reduce(function (int $highest, string $code): int {
            return preg_match('/^VX(\d+)$/', $code, $matches) === 1 ? max($highest, (int) $matches[1]) : $highest;
        }, 0);

        return 'VX'.str_pad((string) ($highest + 1), 4, '0', STR_PAD_LEFT);
    }

    private function seatCountForVehicleType(int $vehicleTypeId): int
    {
        $code = VehicleType::query()->findOrFail($vehicleTypeId)->code;

        return (int) preg_replace('/\D+/', '', $code);
    }
}
