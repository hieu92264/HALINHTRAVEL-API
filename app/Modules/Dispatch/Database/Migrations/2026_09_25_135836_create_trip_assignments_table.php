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
        Schema::create('trip_assignments', function (Blueprint $table) {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();

            $table->foreignId('trip_schedule_id')->constrained('trip_schedules')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('driver_id')->constrained('drivers')->restrictOnDelete();
            $table->foreignId('partner_id')->nullable()->constrained('partners')->nullOnDelete()->comment('Nếu thuê xe ngoài');

            // Phân loại & Lịch sử thay thế
            $table->enum('assignment_type', \App\Shared\Enums\TripAssignmentTypeEnum::values())
                ->comment('primary / substitute');

            // Khóa ngoại tự tham chiếu (Self-referencing FK) đến chính bảng trip_assignments
            $table->foreignId('replaced_assignment_id')->nullable()
                ->constrained('trip_assignments')
                ->nullOnDelete()
                ->comment('Tham chiêu đến assignment có tài xế chính bị thay');

            $table->string('replace_reason', 500)->nullable()->comment('Xe hỏng / tài xế nghỉ');

            // Thời gian & Người thực hiện
            $table->dateTime('assigned_at');
            $table->string('assigned_by', 100);
            $table->foreign('assigned_by')->references('user_name')->on('users')->restrictOnDelete();

            $table->boolean('is_current')->default(true);
            $table->index(['vehicle_id', 'is_current'], 'idx_vehicle_current');
            $table->index(['driver_id', 'is_current'], 'idx_driver_current');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_assignments');
    }
};
