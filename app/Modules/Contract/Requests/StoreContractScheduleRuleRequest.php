<?php

namespace App\Modules\Contract\Requests;

use App\Modules\Contract\DTOs\ContractScheduleRuleData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractScheduleRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contract_item_id' => ['required', 'integer', Rule::exists('contract_items', 'id')],
            'route_id' => ['nullable', 'integer', Rule::exists('routes', 'id')->where('is_active', true)],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['required', 'date', 'after_or_equal:effective_from'],
            'default_vehicle_id' => ['nullable', 'integer', Rule::exists('vehicles', 'id')->where('is_active', true)],
            'default_driver_id' => ['nullable', 'integer', Rule::exists('drivers', 'id')->where('is_active', true)],
            'note' => ['nullable', 'string'],
        ];
    }

    public function toDTO(): ContractScheduleRuleData
    {
        $data = $this->validated();

        return new ContractScheduleRuleData(
            contractItemId: $data['contract_item_id'],
            routeId: $data['route_id'] ?? null,
            effectiveFrom: $data['effective_from'],
            effectiveTo: $data['effective_to'],
            defaultVehicleId: $data['default_vehicle_id'] ?? null,
            defaultDriverId: $data['default_driver_id'] ?? null,
            note: $data['note'] ?? null,
        );
    }
}
