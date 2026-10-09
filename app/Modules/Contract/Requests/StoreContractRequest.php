<?php

namespace App\Modules\Contract\Requests;

use App\Modules\Contract\DTOs\ContractItemData;
use App\Modules\Contract\DTOs\CreateContractData;
use App\Shared\Enums\ContractTypeEnum;
use App\Shared\Enums\RentalServiceTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('is_active', true)],
            'rental_request_id' => ['prohibited'],
            'quotation_id' => ['prohibited'],
            'contract_type' => ['required', Rule::in([ContractTypeEnum::PRINCIPLE->value])],
            'signed_date' => ['required', 'date', 'before_or_equal:effective_from'], 'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'deposit_required' => ['sometimes', 'decimal:0,2', 'min:0'],
            'payment_terms' => ['nullable', 'string'], 'terms' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.route_id' => ['nullable', 'integer', Rule::exists('routes', 'id')->where('is_active', true)],
            'items.*.vehicle_type_id' => ['required', 'integer', Rule::exists('vehicle_types', 'id')->where('is_active', true)],
            'items.*.service_type' => ['required', Rule::enum(RentalServiceTypeEnum::class)],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'decimal:0,2', 'min:0'],
            'items.*.driver_wage' => ['sometimes', 'decimal:0,2', 'min:0'],
            'items.*.pickup_location' => ['nullable', 'string', 'max:500'],
            'items.*.dropoff_location' => ['nullable', 'string', 'max:500'],
            'items.*.note' => ['nullable', 'string'],
        ];
    }

    public function toDTO(): CreateContractData
    {
        $data = $this->validated();

        return new CreateContractData($data['customer_id'], null, null,
            ContractTypeEnum::from($data['contract_type']), $data['signed_date'] ?? null, $data['effective_from'], $data['effective_to'] ?? null,
            (string) ($data['deposit_required'] ?? '0'), $data['payment_terms'] ?? null, $data['terms'] ?? null, $this->items($data['items']));
    }

    /** @param list<array<string,mixed>> $items @return list<ContractItemData> */
    private function items(array $items): array
    {
        return array_map(fn (array $item): ContractItemData => new ContractItemData($item['route_id'] ?? null, $item['vehicle_type_id'], RentalServiceTypeEnum::from($item['service_type']), $item['quantity'], (string) $item['unit_price'], (string) ($item['driver_wage'] ?? '0'), $item['pickup_location'] ?? null, $item['dropoff_location'] ?? null, $item['note'] ?? null), $items);
    }
}
