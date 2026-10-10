<?php

namespace App\Modules\Dispatch\Requests;

class UpdateTripScheduleRequest extends StoreTripScheduleRequest
{
    public function rules(): array
    {
        return collect(parent::rules())->map(fn (array $rules) => array_merge(['sometimes'], $rules))->all();
    }
}
