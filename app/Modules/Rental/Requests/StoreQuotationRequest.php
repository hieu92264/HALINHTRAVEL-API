<?php

namespace App\Modules\Rental\Requests;

use App\Modules\Rental\DTOs\CreateQuotationData;
use App\Modules\Rental\DTOs\QuotationItemData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rental_request_id' => ['nullable', 'integer', Rule::exists('rental_requests', 'id')],
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('is_active', true)],
            'quotation_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:quotation_date'],
            'discount_amount' => ['sometimes', 'decimal:0,2', 'min:0'],
            'payment_terms' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.route_id' => ['nullable', 'integer', Rule::exists('routes', 'id')->where('is_active', true)],
            'items.*.vehicle_type_id' => ['required', 'integer', Rule::exists('vehicle_types', 'id')->where('is_active', true)],
            'items.*.description' => ['nullable', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'decimal:0,2', 'min:0'],
        ];
    }

    public function toDTO(): CreateQuotationData
    {
        $data = $this->validated();

        return new CreateQuotationData(
            rentalRequestId: $data['rental_request_id'] ?? null,
            customerId: $data['customer_id'],
            quotationDate: $data['quotation_date'],
            validUntil: $data['valid_until'] ?? null,
            discountAmount: (string) ($data['discount_amount'] ?? '0'),
            paymentTerms: $data['payment_terms'] ?? null,
            items: array_map(static fn (array $item): QuotationItemData => new QuotationItemData(
                routeId: $item['route_id'] ?? null,
                vehicleTypeId: $item['vehicle_type_id'],
                description: $item['description'] ?? null,
                quantity: $item['quantity'],
                unitPrice: (string) $item['unit_price'],
            ), $data['items']),
        );
    }
}
