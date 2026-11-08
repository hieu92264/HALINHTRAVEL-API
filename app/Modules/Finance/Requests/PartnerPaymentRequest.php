<?php

namespace App\Modules\Finance\Requests;

use App\Shared\Enums\PaymentMethodEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PartnerPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return ['partner_id' => [$required, 'integer', 'exists:partners,id'], 'paid_at' => [$required, 'date'], 'amount' => [$required, 'decimal:0,2', 'min:0'], 'payment_method' => [$required, Rule::enum(PaymentMethodEnum::class)], 'recipient_name' => ['required', 'string', 'max:255'], 'description' => ['sometimes', 'nullable', 'string', 'max:500']];
    }
}
