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
        Schema::create('payroll_item_details', function (Blueprint $table) {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();

            $table->foreignId('payroll_item_id')->constrained('payroll_items')->cascadeOnDelete();
            $table->foreignId('driver_attendance_id')->nullable()->constrained('driver_attendances')->nullOnDelete();
            $table->foreignId('dispatch_order_id')->nullable()->constrained('dispatch_orders')->nullOnDelete();

            // Chi tiết tính toán
            $table->enum('calculation_type', \App\Shared\Enums\PayrollCalculationTypeEnum::values())
                ->comment('fixed_trip / tourism_commission / allowance');
            $table->decimal('base_amount', 18, 2)->default(0);
            $table->decimal('rate', 8, 2)->default(0);
            $table->decimal('amount', 18, 2);

            // Ghi chú
            $table->string('description', 500)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_item_details');
    }
};
