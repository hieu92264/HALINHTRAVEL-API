<?php

namespace App\Modules\MasterData\Requests;

use App\Modules\MasterData\DTOs\CreateVehicleTypeData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateVehicleTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('vehicle_types', 'code')],
            'name' => ['required', 'string', 'max:100'],
            'seats' => ['required', 'integer', 'min:1'],
            'tour_driver_commission_rate' => ['sometimes', 'decimal:0,2', 'min:0', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'mã loại xe',
            'name' => 'tên loại xe',
            'seats' => 'số chỗ',
            'tour_driver_commission_rate' => 'tỷ lệ hoa hồng tài xế tour',
        ];
    }

    public function toDTO(): CreateVehicleTypeData
    {
        $data = $this->validated();

        return new CreateVehicleTypeData(
            code: $data['code'],
            name: $data['name'],
            seats: $data['seats'],
            tour_driver_commission_rate: (string) ($data['tour_driver_commission_rate'] ?? '0'),
        );
    }
}
