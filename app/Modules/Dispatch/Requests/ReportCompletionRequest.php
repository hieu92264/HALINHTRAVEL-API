<?php

namespace App\Modules\Dispatch\Requests;

use App\Modules\Dispatch\DTOs\CompletionReportData;
use Illuminate\Foundation\Http\FormRequest;

class ReportCompletionRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'actual_start_at' => ['required', 'date'], 'actual_end_at' => ['required', 'date', 'after_or_equal:actual_start_at'],
            'start_odometer' => ['required', 'integer', 'min:0'], 'end_odometer' => ['required', 'integer', 'gte:start_odometer'],
            'actual_distance_km' => ['nullable', 'numeric', 'min:0'], 'waiting_hours' => ['nullable', 'numeric', 'min:0'], 'note' => ['nullable', 'string'],
        ];
    }
    public function toDTO(): CompletionReportData
    {
        $data = $this->validated();
        return new CompletionReportData($data['actual_start_at'], $data['actual_end_at'], $data['start_odometer'], $data['end_odometer'], isset($data['actual_distance_km']) ? (string) $data['actual_distance_km'] : null, isset($data['waiting_hours']) ? (string) $data['waiting_hours'] : null, $data['note'] ?? null);
    }
}
