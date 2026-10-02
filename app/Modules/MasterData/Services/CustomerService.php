<?php

namespace App\Modules\MasterData\Services;

use App\Modules\MasterData\DTOs\CreateCustomerData;
use App\Modules\MasterData\DTOs\UpdateCustomerData;
use App\Modules\MasterData\Interfaces\CustomerServiceInterface;
use App\Modules\MasterData\Models\Customer;
use Illuminate\Support\Facades\DB;

class CustomerService implements CustomerServiceInterface
{
    public function customers(): array
    {
        return Customer::query()
            ->orderBy('id')
            ->get()
            ->map(fn (Customer $customer): array => $this->customer($customer))
            ->all();
    }

    public function customer(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'code' => $customer->code,
            'type' => $customer->type?->value,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'cccd' => $customer->cccd,
            'tax_code' => $customer->tax_code,
            'address' => $customer->address,
            'contact_name' => $customer->contact_name,
            'opening_balance' => $customer->opening_balance,
            'is_active' => $customer->is_active,
            'user_name_created' => $customer->user_name_created,
            'user_name_updated' => $customer->user_name_updated,
            'created_at' => $customer->created_at?->toISOString(),
            'updated_at' => $customer->updated_at?->toISOString(),
        ];
    }

    /**
     * @throws \Throwable
     */
    public function create(CreateCustomerData $data): array
    {
        return DB::transaction(function () use ($data): array {
            $customer = Customer::create(
                [
                    ...$data->toArray(),
                    'code' => $this->nextCode(),
                ]
            );

            return $this->customer($customer);
        });
    }

    public function update(Customer $customer, UpdateCustomerData $data): array
    {
        $customer->fill($data->toArray())->save();

        return $this->customer($customer->fresh());
    }

    public function deactivate(Customer $customer): void
    {
        $customer->forceFill(['is_active' => false])->save();
    }

    private function nextCode(): string
    {
        $highestSequence = Customer::query()
            ->where('code', 'like', 'KH%')
            ->lockForUpdate()
            ->pluck('code')
            ->reduce(function (int $highestSequence, string $code): int {
                if (preg_match('/^KH(\d+)$/', $code, $matches) !== 1) {
                    return $highestSequence;
                }

                return max($highestSequence, (int) $matches[1]);
            }, 0);

        return 'KH'.str_pad((string) ($highestSequence + 1), 4, '0', STR_PAD_LEFT);
    }
}
