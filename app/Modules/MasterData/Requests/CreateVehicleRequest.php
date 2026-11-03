<?php

namespace App\Modules\MasterData\Requests;

use App\Modules\MasterData\DTOs\CreateVehicleData;
use App\Shared\Enums\OwnershipTypeEnum;
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
            'seat_count' => ['required', 'integer', 'min:1', 'max:999'],
            'vehicle_model' => ['nullable', 'string', 'max:255'],
            'ownership_type' => ['required', Rule::enum(OwnershipTypeEnum::class)],
            'partner_id' => [Rule::requiredIf($this->input('ownership_type') === OwnershipTypeEnum::PARTNER->value), 'nullable', 'integer', Rule::exists('partners', 'id')],
            'note' => ['nullable', 'string'],
        ];
    }

    public function toDTO(): CreateVehicleData
    {
        $data = $this->validated();

        return new CreateVehicleData(
            license_plate: $data['license_plate'],
            seat_count: $data['seat_count'],
            vehicle_model: $data['vehicle_model'] ?? null,
            ownership_type: OwnershipTypeEnum::from($data['ownership_type']),
            partner_id: $data['partner_id'] ?? null,
            note: $data['note'] ?? null,
        );
    }
}
