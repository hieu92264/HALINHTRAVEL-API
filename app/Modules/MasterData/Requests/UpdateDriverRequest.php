<?php

namespace App\Modules\MasterData\Requests;

use App\Modules\MasterData\DTOs\UpdateDriverData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateDriverRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge(['is_active' => $this->boolean('is_active')]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'responsibility_allowance' => ['sometimes', 'decimal:0,2', 'min:0'],
            'joined_at' => ['sometimes', 'nullable', 'date'],
            'left_at' => ['sometimes', 'nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $driver = $this->route('driver');
            $joinedAt = $this->input('joined_at', $driver?->joined_at?->toDateString());
            $leftAt = $this->input('left_at', $driver?->left_at?->toDateString());
            if ($joinedAt !== null && $leftAt !== null && strtotime($leftAt) < strtotime($joinedAt)) {
                $validator->errors()->add('left_at', 'Ngày nghỉ việc phải bằng hoặc sau ngày vào làm.');
            }
        });
    }

    public function toDTO(): UpdateDriverData
    {
        $data = $this->validated();

        return new UpdateDriverData(
            userName: null, partnerId: null, type: null, fullName: $data['full_name'] ?? null,
            phone: $data['phone'] ?? null, cccd: null, licenseNumber: null, licenseClass: null,
            licenseIssuedAt: null, licenseExpiredAt: null, baseSalary: null,
            responsibilityAllowance: array_key_exists('responsibility_allowance', $data) ? (string) $data['responsibility_allowance'] : null,
            tourismCommissionRate: null, joinedAt: $data['joined_at'] ?? null, leftAt: $data['left_at'] ?? null,
            isActive: array_key_exists('is_active', $data) ? (bool) $data['is_active'] : null,
            provided: array_keys($data),
        );
    }
}
