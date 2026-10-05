<?php

namespace App\Modules\Rental\Requests;

use App\Modules\Rental\DTOs\QuotationItemData;
use App\Modules\Rental\DTOs\UpdateQuotationData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rental_request_id' => ['sometimes', 'nullable', 'integer', Rule::exists('rental_requests', 'id')],
            'customer_id' => ['sometimes', 'integer', Rule::exists('customers', 'id')->where('is_active', true)],
            'quotation_date' => ['sometimes', 'date'],
            'valid_until' => ['sometimes', 'nullable', 'date'],
            'discount_amount' => ['sometimes', 'decimal:0,2', 'min:0'],
            'payment_terms' => ['sometimes', 'nullable', 'string'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.route_id' => ['nullable', 'integer', Rule::exists('routes', 'id')->where('is_active', true)],
            'items.*.vehicle_type_id' => ['required_with:items', 'integer', Rule::exists('vehicle_types', 'id')->where('is_active', true)],
            'items.*.description' => ['nullable', 'string', 'max:500'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_price' => ['required_with:items', 'decimal:0,2', 'min:0'],
        ];
    }

    public function toDTO(): UpdateQuotationData
    {
        $data = $this->validated();
        $items = array_key_exists('items', $data) ? array_map(static fn (array $item): QuotationItemData => new QuotationItemData(
            routeId: $item['route_id'] ?? null,
            vehicleTypeId: $item['vehicle_type_id'],
            description: $item['description'] ?? null,
            quantity: $item['quantity'],
            unitPrice: (string) $item['unit_price'],
        ), $data['items']) : null;
        unset($data['items']);

        return new UpdateQuotationData($data, $items);
    }
}
