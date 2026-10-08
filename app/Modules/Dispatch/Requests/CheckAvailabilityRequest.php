<?php

namespace App\Modules\Dispatch\Requests;

use App\Modules\Dispatch\DTOs\AvailabilityItemData;
use App\Modules\Dispatch\DTOs\CheckAvailabilityData;
use App\Shared\Enums\OwnershipTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.vehicle_type_id' => [
                'required',
                'integer',
                'distinct:strict',
                Rule::exists('vehicle_types', 'id')->where('is_active', true),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'ownership_type' => ['nullable', Rule::enum(OwnershipTypeEnum::class)],
            'partner_id' => [
                'nullable',
                'integer',
                Rule::exists('partners', 'id')->where('is_active', true),
            ],
        ];
    }

    public function toDTO(): CheckAvailabilityData
    {
        $data = $this->validated();

        return new CheckAvailabilityData(
            startAt: $data['start_at'],
            endAt: $data['end_at'],
            items: array_map(
                static fn (array $item): AvailabilityItemData => new AvailabilityItemData(
                    vehicleTypeId: $item['vehicle_type_id'],
                    quantity: $item['quantity'],
                ),
                $data['items'],
            ),
            ownershipType: isset($data['ownership_type'])
                ? OwnershipTypeEnum::from($data['ownership_type'])
                : null,
            partnerId: $data['partner_id'] ?? null,
        );
    }
}
