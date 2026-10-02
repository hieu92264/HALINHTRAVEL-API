<?php

namespace App\Modules\MasterData\Requests;

use App\Modules\MasterData\DTOs\UpdateRouteRateData;
use App\Modules\MasterData\Models\RouteRate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateRouteRateRequest extends FormRequest
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
            'route_id' => ['sometimes', 'integer', Rule::exists('routes', 'id')],
            'vehicle_type_id' => ['sometimes', 'integer', Rule::exists('vehicle_types', 'id')],
            'customer_price' => ['sometimes', 'decimal:0,2', 'min:0'],
            'driver_wage' => ['sometimes', 'decimal:0,2', 'min:0'],
            'effective_from' => ['sometimes', 'date'],
            'effective_to' => ['sometimes', 'nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['effective_from', 'effective_to'])) {
                return;
            }

            $routeRate = $this->route('routeRate');
            $effectiveFrom = $this->input('effective_from', $routeRate instanceof RouteRate
                ? $routeRate->effective_from?->toDateString()
                : null);
            $effectiveTo = $this->has('effective_to')
                ? $this->input('effective_to')
                : ($routeRate instanceof RouteRate ? $routeRate->effective_to?->toDateString() : null);

            if ($effectiveFrom !== null && $effectiveTo !== null && $effectiveTo < $effectiveFrom) {
                $validator->errors()->add('effective_to', 'Ngày kết thúc hiệu lực phải sau hoặc bằng ngày bắt đầu hiệu lực.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'route_id' => 'tuyến đường',
            'vehicle_type_id' => 'loại xe',
            'customer_price' => 'giá khách hàng',
            'driver_wage' => 'lương tài xế',
            'effective_from' => 'ngày bắt đầu hiệu lực',
            'effective_to' => 'ngày kết thúc hiệu lực',
            'is_active' => 'trạng thái hoạt động',
        ];
    }

    public function toDTO(): UpdateRouteRateData
    {
        $data = $this->validated();

        return new UpdateRouteRateData(
            routeId: $data['route_id'] ?? null,
            vehicleTypeId: $data['vehicle_type_id'] ?? null,
            customerPrice: array_key_exists('customer_price', $data) ? (string) $data['customer_price'] : null,
            driverWage: array_key_exists('driver_wage', $data) ? (string) $data['driver_wage'] : null,
            effectiveFrom: $data['effective_from'] ?? null,
            effectiveTo: $data['effective_to'] ?? null,
            isActive: array_key_exists('is_active', $data) ? (bool) $data['is_active'] : null,
            provided: array_keys($data),
        );
    }
}
