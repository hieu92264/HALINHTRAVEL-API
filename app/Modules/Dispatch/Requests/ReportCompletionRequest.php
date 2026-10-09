<?php

namespace App\Modules\Dispatch\Requests;

use App\Modules\Dispatch\DTOs\CompletionReportData;
use Illuminate\Foundation\Http\FormRequest;

class ReportCompletionRequest extends FormRequest
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
            'actual_distance_km' => ['nullable', 'numeric', 'min:0'], 'waiting_hours' => ['nullable', 'numeric', 'min:0'], 'note' => ['nullable', 'string'],
            'customer_amount' => ['prohibited'],
            'partner_vehicle_cost' => ['prohibited'],
            'external_driver_cost' => ['prohibited'],
        ];
    }

    public function toDTO(): CompletionReportData
    {
        $data = $this->validated();

        return new CompletionReportData($data['actual_end_at'], $data['end_odometer'], isset($data['actual_distance_km']) ? (string) $data['actual_distance_km'] : null, isset($data['waiting_hours']) ? (string) $data['waiting_hours'] : null, $data['note'] ?? null);
    }
}
