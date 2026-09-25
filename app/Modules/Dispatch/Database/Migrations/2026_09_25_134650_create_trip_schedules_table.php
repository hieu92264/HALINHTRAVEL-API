<?php

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
        Schema::create('trip_schedules', function (Blueprint $table) {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();

            // Định danh & Liên kết
            $table->string('schedule_no', 50)->unique()->comment('Ví dụ: LT20260925001');
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('contract_item_id')->nullable()->constrained('contract_items')->cascadeOnDelete();
            $table->foreignId('schedule_rule_id')->nullable()->constrained('contract_schedule_rules')
                ->nullOnDelete()
                ->comment('Lịch cố định sinh từ rule');

            // Phân loại & Tuyến đường
            $table->enum('service_type', \App\Shared\Enums\RentalServiceTypeEnum::values())->comment('fixed / tourism / school / business');
            $table->foreignId('route_id')->nullable()->constrained('routes')->nullOnDelete();

            // Thời gian
            $table->dateTime('scheduled_start_at');
            $table->dateTime('scheduled_end_at');

            // Địa điểm & Hành trình
            $table->string('pickup_location', 500)->nullable();
            $table->string('dropoff_location', 500)->nullable();
            $table->text('journey')->nullable()->comment('Hành trình du lịch');

            // Yêu cầu xe
            $table->foreignId('required_vehicle_type_id')->nullable()->constrained('vehicle_types')->nullOnDelete();

            // Trạng thái & Ghi chú
            $table->enum('status', \App\Shared\Enums\TripScheduleStatusEnum::values())->comment('planned / assigned / in_progress / completed / cancelled');
            $table->text('note')->nullable();

            $table->index(['scheduled_start_at', 'scheduled_end_at', 'status'], 'idx_schedule_time_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_schedules');
    }
};
