<?php

namespace App\Modules\MasterData\Requests;

use App\Modules\Auth\Models\User;
use App\Modules\MasterData\DTOs\CreateDriverData;
use App\Modules\MasterData\Models\Driver;
use App\Shared\Enums\OwnershipTypeEnum;
use App\Shared\Enums\PartnerTypeEnum;
use App\Shared\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CreateDriverRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->input('type') === OwnershipTypeEnum::COMPANY->value) {
            $this->merge(['partner_id' => null]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'responsibility_allowance' => ['sometimes', 'decimal:0,2', 'min:0'],
            'joined_at' => ['nullable', 'date'],
            'left_at' => ['nullable', 'date', 'after_or_equal:joined_at'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            // Legacy identity and licence fields are intentionally not accepted for new records.
        });
    }

    public function attributes(): array
    {
        return [
            'full_name' => 'họ và tên',
            'phone' => 'số điện thoại',
            'responsibility_allowance' => 'phụ cấp trách nhiệm',
            'joined_at' => 'ngày vào làm',
            'left_at' => 'ngày nghỉ việc',
        ];
    }

    public function toDTO(): CreateDriverData
    {
        $data = $this->validated();

        return new CreateDriverData(
            userName: null,
            partnerId: null,
            type: OwnershipTypeEnum::COMPANY,
            fullName: $data['full_name'],
            phone: $data['phone'] ?? null,
            cccd: null,
            licenseNumber: null,
            licenseClass: null,
            licenseIssuedAt: null,
            licenseExpiredAt: null,
            baseSalary: '0',
            responsibilityAllowance: (string) ($data['responsibility_allowance'] ?? '0'),
            tourismCommissionRate: '0',
            joinedAt: $data['joined_at'] ?? null,
            leftAt: $data['left_at'] ?? null,
        );
    }
}
