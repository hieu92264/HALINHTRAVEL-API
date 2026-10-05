<?php

namespace App\Modules\Rental\Requests;

use App\Modules\Rental\DTOs\RentalRequestItemData;
use App\Modules\Rental\DTOs\UpdateRentalRequestData;
use App\Shared\Enums\RentalServiceTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRentalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['sometimes', 'integer', Rule::exists('customers', 'id')],
            'source' => ['sometimes', 'nullable', 'string', 'max:30'],
            'requested_at' => ['sometimes', 'date'],
            'service_type' => ['sometimes', Rule::enum(RentalServiceTypeEnum::class)],
            'pickup_location' => ['sometimes', 'nullable', 'string', 'max:500'],
            'dropoff_location' => ['sometimes', 'nullable', 'string', 'max:500'],
            'start_at' => ['sometimes', 'nullable', 'date'],
            'end_at' => [
                'sometimes',
                'nullable',
                'date',
                Rule::when($this->filled('start_at'), ['after_or_equal:start_at']),
            ],
            'note' => ['sometimes', 'nullable', 'string'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.vehicle_type_id' => ['required', 'integer', Rule::exists('vehicle_types', 'id')],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.route_id' => ['nullable', 'integer', Rule::exists('routes', 'id')],
            'items.*.note' => ['nullable', 'string'],
        ];
    }

    public function toDTO(): UpdateRentalRequestData
    {
        $data = $this->validated();

        return new UpdateRentalRequestData(
            customer_id: $data['customer_id'] ?? null,
            source: $data['source'] ?? null,
            requested_at: $data['requested_at'] ?? null,
            service_type: array_key_exists('service_type', $data) ? RentalServiceTypeEnum::from($data['service_type']) : null,
            pickup_location: $data['pickup_location'] ?? null,
            dropoff_location: $data['dropoff_location'] ?? null,
            start_at: $data['start_at'] ?? null,
            end_at: $data['end_at'] ?? null,
            note: $data['note'] ?? null,
            items: array_key_exists('items', $data) ? array_map(
                static fn (array $item): RentalRequestItemData => new RentalRequestItemData(
                    vehicleTypeId: $item['vehicle_type_id'],
                    quantity: $item['quantity'],
                    routeId: $item['route_id'] ?? null,
                    note: $item['note'] ?? null,
                ),
                $data['items'],
            ) : null,
            provided: array_keys($data),
        );
    }
}
