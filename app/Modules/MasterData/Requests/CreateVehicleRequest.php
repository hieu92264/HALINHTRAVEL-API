<?php

namespace App\Modules\MasterData\Requests;

use App\Modules\MasterData\DTOs\CreateVehicleData;
use App\Shared\Enums\OwnershipTypeEnum;
use App\Shared\Enums\VehicleStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateVehicleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->input('ownership_type') === OwnershipTypeEnum::COMPANY->value) {
            $this->merge(['partner_id' => null]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'license_plate' => ['required', 'string', 'max:20', Rule::unique('vehicles', 'license_plate')],
            'vehicle_type_id' => ['required', 'integer', Rule::exists('vehicle_types', 'id')],
            'ownership_type' => ['required', Rule::enum(OwnershipTypeEnum::class)],
            'partner_id' => ['nullable', 'integer', Rule::exists('partners', 'id'), 'required_if:ownership_type,partner'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'manufacture_year' => ['nullable', 'integer', 'min:1886', 'max:9999'],
            'current_odometer' => ['nullable', 'integer', 'min:0'],
            'vehicle_status' => ['required', Rule::enum(VehicleStatusEnum::class)],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'license_plate' => 'biển số xe',
            'vehicle_type_id' => 'loại xe',
            'ownership_type' => 'loại sở hữu',
            'partner_id' => 'đối tác',
            'brand' => 'hãng xe',
            'model' => 'mẫu xe',
            'manufacture_year' => 'năm sản xuất',
            'current_odometer' => 'số công tơ mét hiện tại',
            'vehicle_status' => 'trạng thái xe',
            'notes' => 'ghi chú',
        ];
    }

    public function toDTO(): CreateVehicleData
    {
        $data = $this->validated();

        return new CreateVehicleData(
            licensePlate: $data['license_plate'],
            vehicleTypeId: $data['vehicle_type_id'],
            ownershipType: OwnershipTypeEnum::from($data['ownership_type']),
            partnerId: $data['partner_id'] ?? null,
            brand: $data['brand'] ?? null,
            model: $data['model'] ?? null,
            manufactureYear: $data['manufacture_year'] ?? null,
            currentOdometer: $data['current_odometer'] ?? null,
            vehicleStatus: VehicleStatusEnum::from($data['vehicle_status']),
            notes: $data['notes'] ?? null,
        );
    }
}
