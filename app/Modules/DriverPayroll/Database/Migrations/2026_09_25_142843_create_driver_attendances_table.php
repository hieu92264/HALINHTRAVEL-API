<?php

use App\Shared\Enums\DriverAttendanceStatusEnum;
use App\Shared\Enums\WorkTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('driver_attendances', function (Blueprint $table) {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();

            $table->foreignId('driver_id')->constrained('drivers')->restrictOnDelete();
            $table->foreignId('worker_vehicle_id')->nullable()->constrained('worker_vehicles')->nullOnDelete();
            $table->foreignId('tourist_vehicle_id')->nullable()->constrained('tourist_vehicles')->nullOnDelete();
            $table->unique(['worker_vehicle_id', 'driver_id'], 'worker_vehicle_driver_attendance_unique');
            $table->unique(['tourist_vehicle_id', 'driver_id'], 'tourist_vehicle_driver_attendance_unique');

            // Phân loại & Thời gian
            $table->date('work_date');
            $table->enum('work_type', WorkTypeEnum::values())
                ->comment('fixed_trip / tourism_trip / other');

            // Dữ liệu tính lương
            $table->decimal('work_units', 8, 2)->default(1);
            $table->decimal('base_amount', 18, 2)->default(0)->comment('Giá trị dùng tính lương, ví dụ: tổng tiền cước, tổng tiền tour, ...');
            $table->decimal('rate', 8, 2)->default(0)->comment('Đơn giá hoặc %');
            $table->decimal('calculated_wage', 18, 2)->default(0)->comment('Lương tính toán = base_amount * rate');

            // Trạng thái
            $table->enum('status', DriverAttendanceStatusEnum::values())
                ->default(DriverAttendanceStatusEnum::PENDING)
                ->comment('pending / confirmed / payroll_locked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_attendances');
    }
};
