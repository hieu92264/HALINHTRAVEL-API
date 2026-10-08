<?php

namespace App\Modules\Dispatch\Requests;

use App\Modules\Dispatch\DTOs\TripAssignmentData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTripAssignmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', Rule::exists('vehicles', 'id')->where('is_active', true)],
            'driver_id' => ['required', 'integer', Rule::exists('drivers', 'id')->where('is_active', true)],
            'replace_reason' => ['nullable', 'string', 'max:500'],
        ];
    }
    public function toDTO(): TripAssignmentData
    {
        $data = $this->validated();
        return new TripAssignmentData($data['vehicle_id'], $data['driver_id'], $data['replace_reason'] ?? null);
    }
}
