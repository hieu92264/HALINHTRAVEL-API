<?php

namespace App\Modules\MasterData\Requests;

use App\Modules\MasterData\DTOs\UpdateVehicleTypeData;
use App\Modules\MasterData\Models\VehicleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleTypeRequest extends FormRequest
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
        $vehicleType = $this->route('vehicleType');

        return [
            'code' => [
                'sometimes',
                'string',
                'max:30',
                Rule::unique('vehicle_types', 'code')->ignore($vehicleType instanceof VehicleType ? $vehicleType : null),
            ],
            'name' => ['sometimes', 'string', 'max:100'],
            'seats' => ['sometimes', 'integer', 'min:1'],
            'tour_driver_commission_rate' => ['sometimes', 'decimal:0,2', 'min:0', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'mã loại xe',
            'name' => 'tên loại xe',
            'seats' => 'số chỗ',
            'tour_driver_commission_rate' => 'tỷ lệ hoa hồng tài xế tour',
            'is_active' => 'trạng thái hoạt động',
        ];
    }

    public function toDTO(): UpdateVehicleTypeData
    {
        $data = $this->validated();

        return new UpdateVehicleTypeData(
            code: $data['code'] ?? null,
            name: $data['name'] ?? null,
            seats: $data['seats'] ?? null,
            tour_driver_commission_rate: array_key_exists('tour_driver_commission_rate', $data)
                ? (string) $data['tour_driver_commission_rate']
                : null,
            is_active: array_key_exists('is_active', $data) ? (bool) $data['is_active'] : null,
            provided: array_keys($data),
        );
    }
}
