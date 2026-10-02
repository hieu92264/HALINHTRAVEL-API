<?php

namespace App\Modules\MasterData\Services;

use App\Modules\MasterData\DTOs\CreateDriverData;
use App\Modules\MasterData\DTOs\UpdateDriverData;
use App\Modules\MasterData\Interfaces\DriverServiceInterface;
use App\Modules\MasterData\Models\Driver;
use App\Shared\Enums\OwnershipTypeEnum;
use Illuminate\Support\Facades\DB;

class DriverService implements DriverServiceInterface
{
    public function drivers(): array
    {
        return Driver::query()
            ->orderBy('id')
            ->get()
            ->map(fn (Driver $driver): array => $this->driver($driver))
            ->all();
    }

    public function driver(Driver $driver): array
    {
        return [
            'id' => $driver->id,
            'code' => $driver->code,
            'user_name' => $driver->user_name,
            'partner_id' => $driver->partner_id,
            'type' => $driver->type?->value,
            'full_name' => $driver->full_name,
            'phone' => $driver->phone,
            'cccd' => $driver->cccd,
            'license_number' => $driver->license_number,
            'license_class' => $driver->license_class,
            'license_issued_at' => $driver->license_issued_at?->toDateString(),
            'license_expired_at' => $driver->license_expired_at?->toDateString(),
            'base_salary' => $driver->base_salary,
            'responsibility_allowance' => $driver->responsibility_allowance,
            'joined_at' => $driver->joined_at?->toDateString(),
            'left_at' => $driver->left_at?->toDateString(),
            'is_active' => $driver->is_active,
            'user_name_created' => $driver->user_name_created,
            'user_name_updated' => $driver->user_name_updated,
            'created_at' => $driver->created_at?->toISOString(),
            'updated_at' => $driver->updated_at?->toISOString(),
        ];
    }

    /** @throws \Throwable */
    public function create(CreateDriverData $data): array
    {
        return DB::transaction(function () use ($data): array {
            $driver = Driver::create([
                ...$data->toArray(),
                'code' => $this->nextCode(),
            ]);

            return $this->driver($driver);
        });
    }

    public function update(Driver $driver, UpdateDriverData $data): array
    {
        $changes = $data->toArray();
        $type = $data->type ?? $driver->type;

        if ($type === OwnershipTypeEnum::COMPANY) {
            $changes['partner_id'] = null;
        }

        if (array_key_exists('is_active', $changes)) {
            $driver->forceFill(['is_active' => $changes['is_active']]);
            unset($changes['is_active']);
        }

        $driver->fill($changes)->save();

        return $this->driver($driver->fresh());
    }

    public function deactivate(Driver $driver): void
    {
        $driver->forceFill(['is_active' => false])->save();
    }

    private function nextCode(): string
    {
        $highestSequence = Driver::query()
            ->where('code', 'like', 'LX%')
            ->lockForUpdate()
            ->pluck('code')
            ->reduce(function (int $highestSequence, string $code): int {
                if (preg_match('/^LX(\d+)$/', $code, $matches) !== 1) {
                    return $highestSequence;
                }

                return max($highestSequence, (int) $matches[1]);
            }, 0);

        return 'LX'.str_pad((string) ($highestSequence + 1), 4, '0', STR_PAD_LEFT);
    }
}
