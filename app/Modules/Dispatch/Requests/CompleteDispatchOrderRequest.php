<?php

namespace App\Modules\Dispatch\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteDispatchOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actual_end_at' => ['required', 'date'],
            'end_odometer' => ['required', 'integer', 'min:0'],
            'actual_distance_km' => ['required', 'numeric', 'min:0'],
            'waiting_hours' => ['nullable', 'numeric', 'min:0'],
            'customer_amount' => ['nullable', 'numeric', 'min:0'],
            'partner_vehicle_cost' => ['nullable', 'numeric', 'min:0'],
            'external_driver_cost' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
        ];
    }
}
