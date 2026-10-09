<?php

namespace App\Modules\Dispatch\Requests;

class SubstituteTripAssignmentRequest extends StoreTripAssignmentRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'replace_reason' => ['required', 'string', 'max:500'],
        ]);
    }
}
