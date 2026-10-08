<?php

namespace App\Modules\Contract\Requests;

use App\Modules\Contract\DTOs\UpdateContractScheduleRuleData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContractScheduleRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contract_item_id' => ['sometimes', 'integer', Rule::exists('contract_items', 'id')],
            'route_id' => ['sometimes', 'nullable', 'integer', Rule::exists('routes', 'id')->where('is_active', true)],
            'effective_from' => ['sometimes', 'date'],
            'effective_to' => ['sometimes', 'date'],
            'default_vehicle_id' => ['sometimes', 'nullable', 'integer', Rule::exists('vehicles', 'id')->where('is_active', true)],
            'default_driver_id' => ['sometimes', 'nullable', 'integer', Rule::exists('drivers', 'id')->where('is_active', true)],
            'note' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function toDTO(): UpdateContractScheduleRuleData
    {
        /** @var array<string, int|string|null> $data */
        $data = $this->validated();

        return new UpdateContractScheduleRuleData($data);
    }
}
