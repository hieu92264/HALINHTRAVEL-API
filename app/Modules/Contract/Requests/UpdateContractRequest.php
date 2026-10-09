<?php

namespace App\Modules\Contract\Requests;

use App\Modules\Contract\DTOs\ContractItemData;
use App\Modules\Contract\DTOs\UpdateContractData;
use App\Shared\Enums\ContractTypeEnum;
use App\Shared\Enums\RentalServiceTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['sometimes', 'integer', Rule::exists('customers', 'id')->where('is_active', true)],
            'rental_request_id' => ['prohibited'],
            'quotation_id' => ['prohibited'],
            'contract_type' => ['sometimes', Rule::in([ContractTypeEnum::PRINCIPLE->value])], 'signed_date' => ['sometimes', 'required', 'date'],
            'effective_from' => ['sometimes', 'date'], 'effective_to' => ['sometimes', 'nullable', 'date'],
            'deposit_required' => ['sometimes', 'decimal:0,2', 'min:0'], 'payment_terms' => ['sometimes', 'nullable', 'string'], 'terms' => ['sometimes', 'nullable', 'string'],
            'items' => ['sometimes', 'array', 'min:1'], 'items.*.route_id' => ['nullable', 'integer', Rule::exists('routes', 'id')->where('is_active', true)],
            'items.*.vehicle_type_id' => ['required_with:items', 'integer', Rule::exists('vehicle_types', 'id')->where('is_active', true)],
            'items.*.service_type' => ['required_with:items', Rule::enum(RentalServiceTypeEnum::class)], 'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_price' => ['required_with:items', 'decimal:0,2', 'min:0'], 'items.*.driver_wage' => ['sometimes', 'decimal:0,2', 'min:0'],
            'items.*.pickup_location' => ['nullable', 'string', 'max:500'], 'items.*.dropoff_location' => ['nullable', 'string', 'max:500'], 'items.*.note' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $contract = $this->route('contract');
            $signedDate = $this->input('signed_date', $contract?->signed_date?->toDateString());
            $effectiveFrom = $this->input('effective_from', $contract?->effective_from?->toDateString());
            $effectiveTo = $this->input('effective_to', $contract?->effective_to?->toDateString());

            if ($signedDate !== null && $effectiveFrom !== null && $signedDate > $effectiveFrom) {
                $validator->errors()->add('signed_date', 'Ngày ký không được muộn hơn ngày hiệu lực.');
            }
            if ($effectiveTo !== null && $effectiveFrom !== null && $effectiveTo < $effectiveFrom) {
                $validator->errors()->add('effective_to', 'Ngày kết thúc không được trước ngày hiệu lực.');
            }
        });
    }

    public function toDTO(): UpdateContractData
    {
        $data = $this->validated();
        $items = null;
        if (array_key_exists('items', $data)) {
            $items = array_map(fn (array $item): ContractItemData => new ContractItemData($item['route_id'] ?? null, $item['vehicle_type_id'], RentalServiceTypeEnum::from($item['service_type']), $item['quantity'], (string) $item['unit_price'], (string) ($item['driver_wage'] ?? '0'), $item['pickup_location'] ?? null, $item['dropoff_location'] ?? null, $item['note'] ?? null), $data['items']);
        }
        unset($data['items']);

        return new UpdateContractData($data, $items);
    }
}
