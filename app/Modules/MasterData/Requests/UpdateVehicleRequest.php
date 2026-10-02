<?php

namespace App\Modules\MasterData\Requests;

use App\Modules\MasterData\DTOs\UpdateVehicleData;
use App\Modules\MasterData\Models\Vehicle;
use App\Shared\Enums\OwnershipTypeEnum;
use App\Shared\Enums\VehicleStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateVehicleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge(['is_active' => $this->boolean('is_active')]);
        }

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
        $vehicle = $this->route('vehicle');

        return [
            'license_plate' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('vehicles', 'license_plate')->ignore($vehicle instanceof Vehicle ? $vehicle : null),
            ],
            'vehicle_type_id' => ['sometimes', 'integer', Rule::exists('vehicle_types', 'id')],
            'ownership_type' => ['sometimes', Rule::enum(OwnershipTypeEnum::class)],
            'partner_id' => ['sometimes', 'nullable', 'integer', Rule::exists('partners', 'id')],
            'brand' => ['sometimes', 'nullable', 'string', 'max:100'],
            'model' => ['sometimes', 'nullable', 'string', 'max:100'],
            'manufacture_year' => ['sometimes', 'nullable', 'integer', 'min:1886', 'max:9999'],
            'current_odometer' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'vehicle_status' => ['sometimes', Rule::enum(VehicleStatusEnum::class)],
            'notes' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['ownership_type', 'partner_id'])) {
                return;
            }

            $vehicle = $this->route('vehicle');
            $ownershipType = $this->input('ownership_type', $vehicle instanceof Vehicle ? $vehicle->ownership_type?->value : null);
            $partnerId = $this->has('partner_id') ? $this->input('partner_id') : ($vehicle instanceof Vehicle ? $vehicle->partner_id : null);

            if ($ownershipType === OwnershipTypeEnum::PARTNER->value && $partnerId === null) {
                $validator->errors()->add('partner_id', 'Trường đối tác là bắt buộc khi loại sở hữu là đối tác.');
            }
        });
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
            'is_active' => 'trạng thái hoạt động',
        ];
    }

    public function toDTO(): UpdateVehicleData
    {
        $data = $this->validated();

        return new UpdateVehicleData(
            licensePlate: $data['license_plate'] ?? null,
            vehicleTypeId: $data['vehicle_type_id'] ?? null,
            ownershipType: array_key_exists('ownership_type', $data) ? OwnershipTypeEnum::from($data['ownership_type']) : null,
            partnerId: $data['partner_id'] ?? null,
            brand: $data['brand'] ?? null,
            model: $data['model'] ?? null,
            manufactureYear: $data['manufacture_year'] ?? null,
            currentOdometer: $data['current_odometer'] ?? null,
            vehicleStatus: array_key_exists('vehicle_status', $data) ? VehicleStatusEnum::from($data['vehicle_status']) : null,
            notes: $data['notes'] ?? null,
            isActive: array_key_exists('is_active', $data) ? (bool) $data['is_active'] : null,
            provided: array_keys($data),
        );
    }
}
