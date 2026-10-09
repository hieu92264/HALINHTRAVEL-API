<?php

namespace App\Modules\Dispatch\Requests;

use App\Modules\Dispatch\DTOs\StartDispatchOrderData;
use Illuminate\Foundation\Http\FormRequest;

class StartMyDispatchOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actual_start_at' => ['required', 'date'],
            'start_odometer' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function toDTO(): StartDispatchOrderData
    {
        $data = $this->validated();

        return new StartDispatchOrderData($data['actual_start_at'], $data['start_odometer'], $data['note'] ?? null);
    }
}
