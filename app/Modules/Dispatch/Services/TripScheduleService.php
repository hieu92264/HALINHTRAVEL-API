<?php

namespace App\Modules\Dispatch\Services;

use App\Modules\Contract\Models\Contract;
use App\Modules\Contract\Models\ContractItem;
use App\Modules\Dispatch\DTOs\TripScheduleData;
use App\Modules\Dispatch\Interfaces\TripScheduleServiceInterface;
use App\Modules\Dispatch\Models\TripSchedule;
use App\Shared\Enums\ContractStatusEnum;
use App\Shared\Enums\TripScheduleStatusEnum;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class TripScheduleService implements TripScheduleServiceInterface
{
    public function getList(): array
    {
        return TripSchedule::query()->where('is_active', true)->with($this->relations())->latest('scheduled_start_at')->get()->map(fn (TripSchedule $schedule) => $this->present($schedule))->all();
    }

    public function getDetail(TripSchedule $schedule): array { return $this->present($schedule->load($this->relations())); }

    public function store(TripScheduleData $data): array
    {
        return DB::transaction(function () use ($data): array {
            $values = $data->values;
            $contract = Contract::query()->lockForUpdate()->findOrFail($values['contract_id']);
            if (! $contract->is_active || $contract->status !== ContractStatusEnum::ACTIVE) abort(409, 'Chỉ được tạo lịch cho hợp đồng đang hiệu lực.');
            $item = isset($values['contract_item_id']) ? ContractItem::query()->whereKey($values['contract_item_id'])->where('contract_id', $contract->id)->firstOrFail() : null;
            $start = CarbonImmutable::parse($values['scheduled_start_at']); $end = CarbonImmutable::parse($values['scheduled_end_at']);
            $schedule = TripSchedule::create([...$values, 'schedule_no' => $this->nextNumber($start), 'contract_item_id' => $item?->id, 'status' => TripScheduleStatusEnum::PLANNED]);
            return $this->present($schedule->load($this->relations()));
        });
    }

    public function update(TripSchedule $schedule, TripScheduleData $data): array
    {
        return DB::transaction(function () use ($schedule, $data): array {
            $locked = TripSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
            $this->assertEditable($locked);
            $values = $data->values;
            $start = CarbonImmutable::parse($values['scheduled_start_at'] ?? $locked->scheduled_start_at);
            $end = CarbonImmutable::parse($values['scheduled_end_at'] ?? $locked->scheduled_end_at);
            if ($end->lte($start)) abort(422, 'Thời gian kết thúc phải sau thời gian bắt đầu.');
            $locked->fill($values)->save();
            return $this->present($locked->fresh()->load($this->relations()));
        });
    }

    public function deactivate(TripSchedule $schedule): void
    {
        DB::transaction(function () use ($schedule): void { $locked = TripSchedule::query()->lockForUpdate()->findOrFail($schedule->id); $this->assertEditable($locked); $locked->forceFill(['is_active' => false])->save(); });
    }

    public function cancel(TripSchedule $schedule): array
    {
        return DB::transaction(function () use ($schedule): array {
            $locked = TripSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
            if (in_array($locked->status, [TripScheduleStatusEnum::IN_PROGRESS, TripScheduleStatusEnum::COMPLETED, TripScheduleStatusEnum::CANCELLED], true)) abort(409, 'Không thể hủy lịch đã chạy hoặc đã hoàn tất.');
            if ($locked->dispatchOrders()->whereNot('status', 'CANCELLED')->exists()) abort(409, 'Hãy hủy lệnh điều xe trước khi hủy lịch.');
            $locked->forceFill(['status' => TripScheduleStatusEnum::CANCELLED])->save();
            return $this->present($locked->fresh()->load($this->relations()));
        });
    }

    private function assertEditable(TripSchedule $schedule): void
    {
        if ($schedule->status !== TripScheduleStatusEnum::PLANNED || $schedule->dispatchOrders()->exists()) abort(409, 'Chỉ được sửa hoặc ngừng lịch ở trạng thái đã lập và chưa có lệnh điều xe.');
    }
    private function nextNumber(CarbonImmutable $start): string
    {
        $prefix = 'LT'.$start->format('Ymd');
        $highest = TripSchedule::query()->where('schedule_no', 'like', $prefix.'%')->lockForUpdate()->pluck('schedule_no')->map(fn (string $no) => (int) substr($no, strlen($prefix)))->max() ?? 0;
        return $prefix.str_pad((string) ($highest + 1), 4, '0', STR_PAD_LEFT);
    }
    private function relations(): array { return ['contract:id,contract_no', 'contractItem:id,contract_id,quantity,unit_price', 'route:id,name', 'requiredVehicleType:id,name', 'assignments.vehicle:id,license_plate,partner_id', 'assignments.driver:id,code,full_name', 'assignments.partner:id,name', 'dispatchOrder.tripAssignment']; }
    private function present(TripSchedule $schedule): array { return $schedule->toArray(); }
}
