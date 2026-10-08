<?php

namespace App\Modules\Dashboard\Requests;

use App\Modules\Dashboard\DTOs\DashboardOverviewData;
use Illuminate\Foundation\Http\FormRequest;

class DashboardOverviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function toDTO(): DashboardOverviewData
    {
        $data = $this->validated();

        return new DashboardOverviewData($data['date'] ?? null);
    }
}
