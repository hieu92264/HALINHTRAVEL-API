<?php

namespace App\Modules\MasterData\Requests;

use App\Modules\MasterData\DTOs\CreateRouteData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'shift_name' => ['required', 'string', 'max:100'],
            'pickup_location' => ['required', 'string', 'max:500'],
            'dropoff_location' => ['required', 'string', 'max:500'],
            'default_pickup_time' => ['required', 'date_format:H:i'],
            'default_return_time' => ['required', 'date_format:H:i'],
            'estimated_distance_km' => ['nullable', 'decimal:0,2', 'min:0'],
            'rates' => ['required', 'array', 'size:6'],
            'rates.*.vehicle_type_id' => ['required', 'integer', 'distinct', Rule::exists('vehicle_types', 'id')],
            'rates.*.freight_amount' => ['required', 'decimal:0,2', 'gt:0'],
            'rates.*.driver_wage' => ['required', 'decimal:0,2', 'gt:0'],
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

    public function toDTO(): CreateRouteData
    {
        $data = $this->validated();

        return new CreateRouteData(
            customerId: $data['customer_id'] ?? null,
            name: $data['name'],
            shiftName: $data['shift_name'] ?? null,
            pickupLocation: $data['pickup_location'],
            dropoffLocation: $data['dropoff_location'],
            defaultPickupTime: $data['default_pickup_time'] ?? null,
            defaultReturnTime: $data['default_return_time'] ?? null,
            estimatedDistanceKm: array_key_exists('estimated_distance_km', $data)
                ? ($data['estimated_distance_km'] === null ? null : (string) $data['estimated_distance_km'])
                : null,
            rates: $data['rates'],
        );
    }
}
