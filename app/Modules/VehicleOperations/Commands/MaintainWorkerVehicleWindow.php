<?php

namespace App\Modules\VehicleOperations\Commands;

use App\Modules\VehicleOperations\Models\WorkerVehicleScheduleRule;
use App\Modules\VehicleOperations\Services\VehicleOperationService;
use Illuminate\Console\Command;

class MaintainWorkerVehicleWindow extends Command
{
    protected $signature = 'worker-vehicles:maintain-window';

    protected $description = 'Duy trì các xe chạy công nhân trong cửa sổ 90 ngày tới.';

    public function handle(VehicleOperationService $service): int
    {
        $created = 0;
        $skipped = 0;
        $errors = 0;
        WorkerVehicleScheduleRule::query()->where('is_active', true)->each(function (WorkerVehicleScheduleRule $rule) use ($service, &$created, &$skipped, &$errors): void {
            try {
                $result = $service->syncPlannedRuns($rule);
                $created += count($result['created']);
                $skipped += count($result['skipped']);
            } catch (\Throwable $exception) {
                $errors++;
                $this->warn("Rule {$rule->id}: {$exception->getMessage()}");
            }
        });
        $this->info("Đã duy trì lịch: {$created} chuyến tạo mới, {$skipped} chuyến bỏ qua/đồng bộ, {$errors} rule lỗi.");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
