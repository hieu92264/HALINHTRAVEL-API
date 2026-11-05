<?php

namespace App\Console\Commands;

use App\Modules\MasterData\Models\Route;
use App\Modules\MasterData\Models\Vehicle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditVehicleCatalogConversion extends Command
{
    protected $signature = 'catalog:audit-vehicle-conversion';

    protected $description = 'Báo cáo dữ liệu cần rà soát trước khi áp dụng chuyển đổi loại xe và bảng giá tuyến';

    public function handle(): int
    {
        $hasVehicleType = Schema::hasColumn('vehicles', 'vehicle_type_id');
        $hasDefaultDriver = Schema::hasColumn('vehicles', 'default_driver_id');
        $hasRates = Schema::hasTable('route_vehicle_type_rates');
        $this->table(['Hạng mục', 'Số bản ghi'], [
            ['Xe chưa có loại xe', $hasVehicleType ? Vehicle::query()->whereNull('vehicle_type_id')->count() : Vehicle::query()->count()],
            ['Xe công ty chưa có tài xế mặc định', $hasDefaultDriver ? Vehicle::query()->where('ownership_type', 'company')->whereNull('default_driver_id')->count() : Vehicle::query()->where('ownership_type', 'company')->count()],
            ['Xe đối tác cũ trong danh mục', Vehicle::query()->where('ownership_type', 'partner')->count()],
            ['Tuyến thiếu đủ sáu loại giá', $hasRates ? Route::query()->whereRaw('(select count(*) from route_vehicle_type_rates where route_vehicle_type_rates.route_id = routes.id) <> 6')->count() : Route::query()->count()],
            ['Dòng giá tuyến trùng loại xe', $hasRates ? DB::table('route_vehicle_type_rates')->select('route_id', 'vehicle_type_id')->groupBy('route_id', 'vehicle_type_id')->havingRaw('count(*) > 1')->count() : 0],
        ]);

        $this->line('Lệnh chỉ đọc dữ liệu; hãy lưu báo cáo này và tạo bản sao lưu trước khi chạy migration ở môi trường thật.');

        return self::SUCCESS;
    }
}
