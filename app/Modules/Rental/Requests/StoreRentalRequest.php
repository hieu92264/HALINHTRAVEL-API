<?php

namespace App\Modules\Rental\Requests;

use App\Modules\Rental\DTOs\CreateRentalRequestData;
use App\Modules\Rental\DTOs\RentalRequestItemData;
use App\Shared\Enums\RentalServiceTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreRentalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('is_active', true)],
            'source' => ['nullable', 'string', 'max:30'],
            'requested_at' => ['required', 'date', 'before_or_equal:now', 'before_or_equal:start_at'],
            'service_type' => ['required', Rule::enum(RentalServiceTypeEnum::class)],
            'pickup_location' => ['nullable', 'string', 'max:500'],
            'dropoff_location' => ['nullable', 'string', 'max:500'],
            'start_at' => ['required', 'date', 'after:now'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.vehicle_type_id' => ['required', 'integer', Rule::exists('vehicle_types', 'id')->where('is_active', true)],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.route_id' => ['nullable', 'integer', Rule::exists('routes', 'id')->where('is_active', true)],
            'items.*.note' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $pickup = $this->normalizeLocation($this->input('pickup_location'));
            $dropoff = $this->normalizeLocation($this->input('dropoff_location'));
            if ($pickup !== null && $pickup === $dropoff) {
                $validator->errors()->add('dropoff_location', 'Điểm trả phải khác điểm đón.');
            }

            /** @var list<array{route_id?: int|string|null}> $items */
            $items = $this->input('items', []);
            if (! is_array($items) || $items === [] || collect($items)->contains(static fn (mixed $item): bool => ! is_array($item))) {
                return;
            }

            if (collect($validator->errors()->keys())->contains(
                static fn (string $key): bool => $key === 'items' || str_starts_with($key, 'items.'),
            )) {
                return;
            }

            $itemsWithRoute = collect($items)->filter(
                static fn (array $item): bool => ($item['route_id'] ?? null) !== null && ($item['route_id'] ?? '') !== '',
            );
            $routeIds = $itemsWithRoute
                ->pluck('route_id')
                ->map(static fn (int|string $routeId): int => (int) $routeId)
                ->unique()
                ->values();

            if ($routeIds->isEmpty()) {
                foreach (['pickup_location', 'dropoff_location'] as $field) {
                    if (! $this->filled($field)) {
                        $validator->errors()->add($field, 'Trường này là bắt buộc khi nhập hành trình.');
                    }
                }

                return;
            }

            if ($routeIds->count() !== 1 || $itemsWithRoute->count() !== count($items)) {
                $validator->errors()->add('items', 'Tất cả hạng mục phải dùng cùng một tuyến hoặc đều không chọn tuyến.');
            }
        });
    }

    private function normalizeLocation(mixed $value): ?string
    {
        if (! is_string($value) || blank($value)) {
            return null;
        }

        return mb_strtoupper(preg_replace('/\s+/u', ' ', trim($value)) ?? '');
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
