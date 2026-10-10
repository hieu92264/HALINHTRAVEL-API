<?php

namespace App\Modules\Finance\Requests;

use App\Shared\Enums\PaymentMethodEnum;
use App\Shared\Enums\ReceiptTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return ['customer_id' => [$required, 'integer', 'exists:customers,id'], 'contract_id' => ['sometimes', 'nullable', 'integer', 'exists:contracts,id'], 'receipt_type' => [$required, Rule::enum(ReceiptTypeEnum::class)], 'received_at' => [$required, 'date'], 'amount' => [$required, 'decimal:0,2', 'min:0'], 'payment_method' => [$required, Rule::enum(PaymentMethodEnum::class)], 'payer_name' => ['sometimes', 'nullable', 'string', 'max:255'], 'description' => ['sometimes', 'nullable', 'string', 'max:500']];
    }
}
