<?php

namespace App\Modules\MasterData\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LookupRouteRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'route_id' => ['required', 'integer', Rule::exists('routes', 'id')],
            'vehicle_type_id' => ['required', 'integer', Rule::exists('vehicle_types', 'id')],
            'at_date' => ['required', 'date'],
        ];
    }

    /** @return array{route_id: int, vehicle_type_id: int, at_date: string} */
    public function criteria(): array
    {
        $data = $this->validated();

        return [
            'route_id' => $data['route_id'],
            'vehicle_type_id' => $data['vehicle_type_id'],
            'at_date' => $data['at_date'],
        ];
    }
}
