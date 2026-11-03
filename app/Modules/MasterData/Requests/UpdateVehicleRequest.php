<?php

namespace App\Modules\MasterData\Requests;

use App\Modules\MasterData\DTOs\UpdateVehicleData;
use App\Modules\MasterData\Models\Vehicle;
use App\Shared\Enums\OwnershipTypeEnum;
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
            'seat_count' => ['sometimes', 'integer', 'min:1', 'max:999'],
            'vehicle_model' => ['sometimes', 'nullable', 'string', 'max:255'],
            'ownership_type' => ['sometimes', Rule::enum(OwnershipTypeEnum::class)],
            'partner_id' => ['sometimes', 'nullable', 'integer', Rule::exists('partners', 'id')],
            'note' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function toDTO(): UpdateVehicleData
    {
        $data = $this->validated();

        return new UpdateVehicleData(
            license_plate: $data['license_plate'] ?? null,
            seat_count: $data['seat_count'] ?? null,
            vehicle_model: $data['vehicle_model'] ?? null,
            ownership_type: array_key_exists('ownership_type', $data) ? OwnershipTypeEnum::from($data['ownership_type']) : null,
            partner_id: $data['partner_id'] ?? null,
            note: $data['note'] ?? null,
            is_active: array_key_exists('is_active', $data) ? (bool) $data['is_active'] : null,
            provided: array_keys($data),
        );
    }
}
