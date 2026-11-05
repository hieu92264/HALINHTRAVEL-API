<?php

namespace App\Modules\MasterData\Requests;

use App\Modules\MasterData\DTOs\UpdateVehicleData;
use App\Modules\MasterData\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('license_plate')) {
            $this->merge(['license_plate' => strtoupper(trim((string) $this->input('license_plate')))]);
        }
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
        /** @var Vehicle|null $vehicle */
        $vehicle = $this->route('vehicle');

        return [
            'license_plate' => ['sometimes', 'string', 'max:50', Rule::unique('vehicles', 'license_plate')->ignore($vehicle?->id)],
            'vehicle_type_id' => ['sometimes', 'integer', Rule::exists('vehicle_types', 'id')],
            'default_driver_id' => ['sometimes', 'integer', Rule::exists('drivers', 'id')],
            'note' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function toDTO(): UpdateVehicleData
    {
        $data = $this->validated();

        return new UpdateVehicleData(
            license_plate: $data['license_plate'] ?? null,
            vehicle_type_id: $data['vehicle_type_id'] ?? null,
            default_driver_id: $data['default_driver_id'] ?? null,
            note: $data['note'] ?? null,
            is_active: array_key_exists('is_active', $data) ? (bool) $data['is_active'] : null,
            provided: array_keys($data),
        );
    }
}
