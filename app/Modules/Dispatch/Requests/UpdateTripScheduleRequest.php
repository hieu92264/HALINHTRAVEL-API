<?php

namespace App\Modules\Dispatch\Requests;

use App\Modules\Dispatch\DTOs\TripScheduleData;
use App\Shared\Enums\RentalServiceTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTripScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_type' => ['sometimes', Rule::enum(RentalServiceTypeEnum::class)],
            'route_id' => ['nullable', 'integer', Rule::exists('routes', 'id')->where('is_active', true)],
            'scheduled_start_at' => ['sometimes', 'date'],
            'scheduled_end_at' => ['sometimes', 'date'],
            'pickup_location' => ['nullable', 'string', 'max:500'],
            'dropoff_location' => ['nullable', 'string', 'max:500'],
            'journey' => ['nullable', 'string'],
            'required_vehicle_type_id' => ['nullable', 'integer', Rule::exists('vehicle_types', 'id')->where('is_active', true)],
            'note' => ['nullable', 'string'],
        ];
    }

    public function toDTO(): TripScheduleData
    {
        return new TripScheduleData($this->validated());
    }
}
