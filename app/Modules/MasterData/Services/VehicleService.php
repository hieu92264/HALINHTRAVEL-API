<?php

namespace App\Modules\MasterData\Services;

use App\Modules\MasterData\DTOs\CreateVehicleData;
use App\Modules\MasterData\DTOs\UpdateVehicleData;
use App\Modules\MasterData\Interfaces\VehicleServiceInterface;
use App\Modules\MasterData\Models\Vehicle;
use App\Shared\Enums\OwnershipTypeEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VehicleService implements VehicleServiceInterface
{
    public function vehicles(): array
    {
        return Vehicle::query()->with('partner:id,name')->orderBy('id')->get()->map(fn (Vehicle $vehicle): array => $this->vehicle($vehicle))->all();
    }

    public function options(?string $ownershipType = null): array
    {
        return Vehicle::query()
            ->with('partner:id,name')
            ->active()
            ->when($ownershipType, fn ($query) => $query->where('ownership_type', $ownershipType))
            ->orderBy('license_plate')
            ->get()
            ->map(fn (Vehicle $vehicle): array => $this->vehicle($vehicle))
            ->all();
    }

    public function vehicle(Vehicle $vehicle): array
    {
        $vehicle->loadMissing('partner:id,name');

        return [
            'id' => $vehicle->id,
            'code' => $vehicle->code,
            'license_plate' => $vehicle->license_plate,
            'seat_count' => $vehicle->seat_count,
            'vehicle_model' => $vehicle->vehicle_model,
            'ownership_type' => $vehicle->ownership_type?->value,
            'partner_id' => $vehicle->partner_id,
            'partner_name' => $vehicle->partner?->name,
            'note' => $vehicle->note,
            'is_active' => $vehicle->is_active,
            'created_at' => $vehicle->created_at?->toISOString(),
            'updated_at' => $vehicle->updated_at?->toISOString(),
        ];
    }

    public function create(CreateVehicleData $data): array
    {
        $this->assertOwnership($data->ownership_type, $data->partner_id);

        return DB::transaction(function () use ($data): array {
            $vehicle = Vehicle::create([...$data->toArray(), 'code' => $this->nextCode()]);

            return $this->vehicle($vehicle);
        });
    }

    public function update(Vehicle $vehicle, UpdateVehicleData $data): array
    {
        $changes = $data->toArray();
        $ownership = array_key_exists('ownership_type', $changes) ? OwnershipTypeEnum::from($changes['ownership_type']) : $vehicle->ownership_type;
        $partnerId = array_key_exists('partner_id', $changes) ? $changes['partner_id'] : $vehicle->partner_id;
        $this->assertOwnership($ownership, $partnerId);

        if ($ownership === OwnershipTypeEnum::COMPANY) {
            $changes['partner_id'] = null;
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

    private function assertOwnership(OwnershipTypeEnum $ownership, ?int $partnerId): void
    {
        if ($ownership === OwnershipTypeEnum::PARTNER && ! $partnerId) {
            throw ValidationException::withMessages(['partner_id' => 'Xe đối tác phải có đối tác chủ xe.']);
        }
    }

    private function nextCode(): string
    {
        $highest = Vehicle::query()->where('code', 'like', 'VX%')->lockForUpdate()->pluck('code')->reduce(function (int $highest, string $code): int {
            return preg_match('/^VX(\d+)$/', $code, $matches) === 1 ? max($highest, (int) $matches[1]) : $highest;
        }, 0);

        return 'VX'.str_pad((string) ($highest + 1), 4, '0', STR_PAD_LEFT);
    }
}
