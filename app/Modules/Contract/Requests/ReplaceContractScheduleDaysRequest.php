<?php

namespace App\Modules\Contract\Requests;

use App\Modules\Contract\DTOs\ContractScheduleDayData;
use App\Shared\Enums\WeekdayEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReplaceContractScheduleDaysRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'days' => ['required', 'array', 'min:1'],
            'days.*.weekday' => ['required', Rule::enum(WeekdayEnum::class)],
            'days.*.pickup_time' => ['required', 'date_format:H:i'],
            'days.*.return_time' => ['nullable', 'date_format:H:i'],
            'days.*.shift_name' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $days = $this->input('days', []);
            if (! is_array($days)) {
                return;
            }

            $seen = [];
            foreach ($days as $index => $day) {
                if (! is_array($day) || ! isset($day['weekday'], $day['pickup_time'])) {
                    continue;
                }

                $key = $day['weekday'].'|'.$day['pickup_time'];
                if (isset($seen[$key])) {
                    $validator->errors()->add("days.{$index}.pickup_time", 'Không được trùng thứ và giờ đón trong cùng quy tắc.');
                }
                $seen[$key] = true;

                if (($day['return_time'] ?? null) !== null && $day['return_time'] !== '' && $day['return_time'] <= $day['pickup_time']) {
                    $validator->errors()->add("days.{$index}.return_time", 'Giờ về phải sau giờ đón trong cùng ngày.');
                }
            }
        });
    }

    /** @return list<ContractScheduleDayData> */
    public function toDTO(): array
    {
        /** @var list<array{weekday:string,pickup_time:string,return_time?:string|null,shift_name?:string|null}> $days */
        $days = $this->validated('days');

        return array_map(
            static fn (array $day): ContractScheduleDayData => new ContractScheduleDayData(
                weekday: WeekdayEnum::from($day['weekday']),
                pickupTime: $day['pickup_time'],
                returnTime: $day['return_time'] ?? null,
                shiftName: $day['shift_name'] ?? null,
            ),
            $days,
        );
    }
}
