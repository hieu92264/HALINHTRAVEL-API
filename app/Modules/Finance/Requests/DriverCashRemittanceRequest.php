<?php

namespace App\Modules\Finance\Requests;

use App\Shared\Enums\PaymentMethodEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DriverCashRemittanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'receipt_id' => [$required, 'integer', 'exists:receipts,id'],
            'dispatch_order_id' => [$required, 'integer', 'exists:dispatch_orders,id'],
            'driver_id' => [$required, 'integer', 'exists:drivers,id'],
            'remitted_at' => [$required, 'date'],
            'amount' => [$required, 'decimal:0,2', 'gt:0'],
            'payment_method' => [$required, Rule::enum(PaymentMethodEnum::class)],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
