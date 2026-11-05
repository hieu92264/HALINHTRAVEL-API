<?php

namespace App\Modules\MasterData\Services;

use App\Modules\MasterData\DTOs\CreatePartnerData;
use App\Modules\MasterData\DTOs\UpdatePartnerData;
use App\Modules\MasterData\Interfaces\PartnerServiceInterface;
use App\Modules\MasterData\Models\Partner;
use App\Shared\Enums\PartnerTypeEnum;
use Illuminate\Support\Facades\DB;

class PartnerService implements PartnerServiceInterface
{
    public function partners(): array
    {
        return Partner::query()
            ->whereIn('type', [PartnerTypeEnum::VEHICLE_OWNER->value, PartnerTypeEnum::TRANSPORT_COMPANY->value])
            ->orderBy('id')
            ->get()
            ->map(fn (Partner $partner): array => $this->partner($partner))
            ->all();
    }

    public function options(array $types = []): array
    {
        return Partner::query()
            ->active()
            ->when($types !== [], fn ($query) => $query->whereIn('type', $types))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static fn (Partner $partner): array => [
                'id' => $partner->id,
                'name' => $partner->name,
            ])
            ->all();
    }

    public function partner(Partner $partner): array
    {
        return [
            'id' => $partner->id,
            'code' => $partner->code,
            'name' => $partner->name,
            'phone' => $partner->phone,
            'cccd' => $partner->cccd,
            'address' => $partner->address,
            'opening_balance' => $partner->opening_balance,
            'is_active' => $partner->is_active,
            'user_name_created' => $partner->user_name_created,
            'user_name_updated' => $partner->user_name_updated,
            'created_at' => $partner->created_at?->toISOString(),
            'updated_at' => $partner->updated_at?->toISOString(),
        ];
    }

    /** @throws \Throwable */
    public function create(CreatePartnerData $data): array
    {
        return DB::transaction(function () use ($data): array {
            $partner = Partner::create([
                ...$data->toArray(),
                'type' => PartnerTypeEnum::VEHICLE_OWNER->value,
                'code' => $this->nextCode(),
            ]);

            return $this->partner($partner);
        });
    }

    public function update(Partner $partner, UpdatePartnerData $data): array
    {
        $changes = $data->toArray();
        unset($changes['type'], $changes['email'], $changes['tax_code'], $changes['bank_name'], $changes['bank_account']);
        $partner->fill($changes)->save();

        return $this->partner($partner->fresh());
    }

    public function deactivate(Partner $partner): void
    {
        $partner->forceFill(['is_active' => false])->save();
    }

    private function nextCode(): string
    {
        $highestSequence = Partner::query()
            ->where('code', 'like', 'DT%')
            ->lockForUpdate()
            ->pluck('code')
            ->reduce(function (int $highestSequence, string $code): int {
                if (preg_match('/^DT(\d+)$/', $code, $matches) !== 1) {
                    return $highestSequence;
                }

                return max($highestSequence, (int) $matches[1]);
            }, 0);

        return 'DT'.str_pad((string) ($highestSequence + 1), 4, '0', STR_PAD_LEFT);
    }
}
