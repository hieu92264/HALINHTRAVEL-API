<?php

namespace App\Modules\Rental\Requests;

use App\Modules\Rental\DTOs\CreateRentalRequestData;
use App\Modules\Rental\DTOs\RentalRequestItemData;
use App\Shared\Enums\RentalServiceTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRentalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')],
            'source' => ['nullable', 'string', 'max:30'],
            'requested_at' => ['required', 'date'],
            'service_type' => ['required', Rule::enum(RentalServiceTypeEnum::class)],
            'pickup_location' => ['nullable', 'string', 'max:500'],
            'dropoff_location' => ['nullable', 'string', 'max:500'],
            'start_at' => ['nullable', 'date'],
            'end_at' => [
                'nullable',
                'date',
                Rule::when($this->filled('start_at'), ['after_or_equal:start_at']),
            ],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.vehicle_type_id' => ['required', 'integer', Rule::exists('vehicle_types', 'id')],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.route_id' => ['nullable', 'integer', Rule::exists('routes', 'id')],
            'items.*.note' => ['nullable', 'string'],
        ];
    }

    public function toDTO(): CreateRentalRequestData
    {
        $data = $this->validated();

        return new CreateRentalRequestData(
            customer_id: $data['customer_id'],
            source: $data['source'] ?? null,
            requested_at: $data['requested_at'],
            service_type: RentalServiceTypeEnum::from($data['service_type']),
            pickup_location: $data['pickup_location'] ?? null,
            dropoff_location: $data['dropoff_location'] ?? null,
            start_at: $data['start_at'] ?? null,
            end_at: $data['end_at'] ?? null,
            note: $data['note'] ?? null,
            items: array_map(
                static fn (array $item): RentalRequestItemData => new RentalRequestItemData(
                    vehicleTypeId: $item['vehicle_type_id'],
                    quantity: $item['quantity'],
                    routeId: $item['route_id'] ?? null,
                    note: $item['note'] ?? null,
                ),
                $data['items'],
            ),
        );
    }
}
