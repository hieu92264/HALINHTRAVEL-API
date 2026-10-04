<?php

namespace App\Modules\MasterData\Requests;

use App\Modules\MasterData\DTOs\UpdateRouteData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRouteRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge(['is_active' => $this->boolean('is_active')]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['sometimes', 'nullable', 'integer', Rule::exists('customers', 'id')],
            'name' => ['sometimes', 'string', 'max:255'],
            'shift_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'pickup_location' => ['sometimes', 'string', 'max:500'],
            'dropoff_location' => ['sometimes', 'string', 'max:500'],
            'default_pickup_time' => ['sometimes', 'nullable', 'date_format:H:i'],
            'default_return_time' => ['sometimes', 'nullable', 'date_format:H:i'],
            'estimated_distance_km' => ['sometimes', 'nullable', 'decimal:0,2', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'khách hàng',
            'name' => 'tên tuyến',
            'shift_name' => 'tên ca',
            'pickup_location' => 'điểm đón',
            'dropoff_location' => 'điểm trả',
            'default_pickup_time' => 'giờ đón mặc định',
            'default_return_time' => 'giờ về mặc định',
            'estimated_distance_km' => 'khoảng cách ước tính',
        ];
    }

    public function toDTO(): UpdateRouteData
    {
        $data = $this->validated();

        return new UpdateRouteData(
            customerId: $data['customer_id'] ?? null,
            name: $data['name'] ?? null,
            shiftName: $data['shift_name'] ?? null,
            pickupLocation: $data['pickup_location'] ?? null,
            dropoffLocation: $data['dropoff_location'] ?? null,
            defaultPickupTime: $data['default_pickup_time'] ?? null,
            defaultReturnTime: $data['default_return_time'] ?? null,
            estimatedDistanceKm: array_key_exists('estimated_distance_km', $data)
                ? (string) $data['estimated_distance_km']
                : null,
            isActive: array_key_exists('is_active', $data) ? (bool) $data['is_active'] : null,
            provided: array_keys($data),
        );
    }
}
