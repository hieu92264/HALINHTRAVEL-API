<?php

namespace App\Modules\MasterData\Requests;

use App\Modules\MasterData\DTOs\CreateRouteRateData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRouteRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'route_id' => ['required', 'integer', Rule::exists('routes', 'id')],
            'vehicle_type_id' => ['required', 'integer', Rule::exists('vehicle_types', 'id')],
            'customer_price' => ['required', 'decimal:0,2', 'min:0'],
            'driver_wage' => ['sometimes', 'decimal:0,2', 'min:0'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
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
        ];
    }

    public function toDTO(): CreateRouteRateData
    {
        $data = $this->validated();

        return new CreateRouteRateData(
            routeId: $data['route_id'],
            vehicleTypeId: $data['vehicle_type_id'],
            customerPrice: (string) $data['customer_price'],
            driverWage: (string) ($data['driver_wage'] ?? '0'),
            effectiveFrom: $data['effective_from'],
            effectiveTo: $data['effective_to'] ?? null,
        );
    }
}
