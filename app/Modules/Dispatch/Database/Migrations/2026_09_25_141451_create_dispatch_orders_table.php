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
        Schema::create('dispatch_orders', function (Blueprint $table) {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();

            // Định danh & Liên kết
            $table->string('order_no', 50)->unique()->comment('Ví dụ: LDX20260001');
            $table->foreignId('trip_schedule_id')->unique()->constrained('trip_schedules')->restrictOnDelete();
            $table->foreignId('trip_assignment_id')->constrained('trip_assignments')
                ->restrictOnDelete()
                ->comment('Assignment hiện hành');

            // Thời gian & Người ban hành
            $table->dateTime('issued_at'); // DATETIME[cite: 20]
            // Không có NULL theo thiết kế, nên dùng restrictOnDelete để tránh lỗi mất người ban hành
            $table->foreignId('issued_by')->constrained('users')->restrictOnDelete();

            // Dữ liệu thực tế chuyến đi
            $table->dateTime('actual_start_at')->nullable(); // DATETIME NULL[cite: 20]
            $table->dateTime('actual_end_at')->nullable(); // DATETIME NULL[cite: 20]
            $table->unsignedInteger('start_odometer')->nullable(); // INT UNSIGNED NULL[cite: 20]
            $table->unsignedInteger('end_odometer')->nullable(); // INT UNSIGNED NULL[cite: 20]
            $table->decimal('actual_distance_km', 10, 2)->nullable()->comment('Khoảng cách thực tế');
            $table->decimal('waiting_hours', 6, 2)->default(0)->comment('Thời gian chờ thực tế (giờ)');

            // Tài chính (Doanh thu & Chi phí)
            $table->decimal('customer_amount', 18, 2)->default(0)->comment('Doanh thu chuyến');
            $table->decimal('partner_vehicle_cost', 18, 2)->default(0)->comment('Nếu xe ngoài');
            $table->decimal('external_driver_cost', 18, 2)->default(0)->comment('Nếu thuê lái');

            // Trạng thái & Ghi chú
            $table->enum('status', \App\Shared\Enums\DispatchOrderStatusEnum::values())
                ->comment('issued / accepted / in_progress / completed / cancelled');
            $table->dateTime('completed_at')->nullable();
            $table->text('note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispatch_orders');
    }
};
