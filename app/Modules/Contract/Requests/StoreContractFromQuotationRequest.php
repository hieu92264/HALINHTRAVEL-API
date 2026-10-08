<?php

namespace App\Modules\Contract\Requests;

use App\Modules\Contract\DTOs\CreateContractFromQuotationData;
use App\Shared\Enums\ContractTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractFromQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quotation_id' => ['required', 'integer', Rule::exists('quotations', 'id')], 'contract_type' => ['required', Rule::in([ContractTypeEnum::TRIP->value])],
            'signed_date' => ['nullable', 'date'], 'effective_from' => ['required', 'date'], 'effective_to' => ['required', 'date', 'after_or_equal:effective_from'],
            'deposit_required' => ['sometimes', 'decimal:0,2', 'min:0'], 'payment_terms' => ['nullable', 'string'], 'terms' => ['nullable', 'string'],
        ];
    }

    public function toDTO(): CreateContractFromQuotationData
    {
        $d = $this->validated();

        return new CreateContractFromQuotationData($d['quotation_id'], ContractTypeEnum::from($d['contract_type']), $d['signed_date'] ?? null, $d['effective_from'], $d['effective_to'] ?? null, (string) ($d['deposit_required'] ?? '0'), $d['payment_terms'] ?? null, $d['terms'] ?? null);
    }
}
