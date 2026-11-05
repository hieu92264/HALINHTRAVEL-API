<?php

namespace App\Modules\MasterData\Requests;

use App\Modules\MasterData\DTOs\CreateVehicleData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateVehicleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('license_plate')) {
            $this->merge(['license_plate' => strtoupper(trim((string) $this->input('license_plate')))]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'license_plate' => ['required', 'string', 'max:50', 'unique:vehicles,license_plate'],
            'vehicle_type_id' => ['required', 'integer', Rule::exists('vehicle_types', 'id')],
            'default_driver_id' => ['required', 'integer', Rule::exists('drivers', 'id')],
            'note' => ['nullable', 'string'],
        ];
    }

    public function toDTO(): CreateVehicleData
    {
        $data = $this->validated();

        return new CreateVehicleData(
            license_plate: $data['license_plate'],
            vehicle_type_id: $data['vehicle_type_id'],
            default_driver_id: $data['default_driver_id'],
            note: $data['note'] ?? null,
        );
    }
}
