<?php

namespace App\Modules\Dispatch\Requests;

use App\Modules\Dispatch\DTOs\CompletionConfirmationData;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmCompletionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['customer_amount' => ['required', 'numeric', 'min:0'], 'partner_vehicle_cost' => ['nullable', 'numeric', 'min:0'], 'external_driver_cost' => ['nullable', 'numeric', 'min:0'], 'note' => ['nullable', 'string']];
    }

    public function toDTO(): CompletionConfirmationData
    {
        $data = $this->validated();

        return new CompletionConfirmationData((string) $data['customer_amount'], (string) ($data['partner_vehicle_cost'] ?? 0), (string) ($data['external_driver_cost'] ?? 0), $data['note'] ?? null);
    }
}
