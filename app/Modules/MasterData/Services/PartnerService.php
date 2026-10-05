<?php

namespace App\Modules\MasterData\Services;

use App\Modules\MasterData\DTOs\CreatePartnerData;
use App\Modules\MasterData\DTOs\UpdatePartnerData;
use App\Modules\MasterData\Interfaces\PartnerServiceInterface;
use App\Modules\MasterData\Models\Partner;
use Illuminate\Support\Facades\DB;

class PartnerService implements PartnerServiceInterface
{
    public function partners(): array
    {
        return Partner::query()
            ->orderBy('id')
            ->get()
            ->map(fn (Partner $partner): array => $this->partner($partner))
            ->all();
    }

    public function options(): array
    {
        return Partner::query()
            ->active()
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
            'type' => $partner->type?->value,
            'name' => $partner->name,
            'phone' => $partner->phone,
            'email' => $partner->email,
            'cccd' => $partner->cccd,
            'tax_code' => $partner->tax_code,
            'address' => $partner->address,
            'bank_name' => $partner->bank_name,
            'bank_account' => $partner->bank_account,
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
                'code' => $this->nextCode(),
            ]);

            return $this->partner($partner);
        });
    }

    public function update(Partner $partner, UpdatePartnerData $data): array
    {
        $partner->fill($data->toArray())->save();

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
