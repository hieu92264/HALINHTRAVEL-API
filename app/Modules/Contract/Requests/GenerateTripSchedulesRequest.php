<?php

namespace App\Modules\Contract\Requests;

use App\Modules\Contract\DTOs\GenerateTripSchedulesData;
use Illuminate\Foundation\Http\FormRequest;

class GenerateTripSchedulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
        ];
    }

    public function toDTO(): GenerateTripSchedulesData
    {
        $data = $this->validated();

        return new GenerateTripSchedulesData($data['from_date'], $data['to_date']);
    }
}
