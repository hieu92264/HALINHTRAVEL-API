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
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();

            $table->foreignId('payroll_id')->constrained('payrolls')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('drivers')->restrictOnDelete();

            // Các khoản thu nhập
            $table->decimal('base_salary', 18, 2)->default(0);
            $table->decimal('responsibility_allowance', 18, 2)->default(0);
            $table->decimal('meal_allowance', 18, 2)->default(0);
            $table->decimal('fixed_trip_wage', 18, 2)->default(0);
            $table->decimal('tourism_commission', 18, 2)->default(0);
            $table->decimal('other_allowance', 18, 2)->default(0);

            // Các khoản khấu trừ & Tạm ứng
            $table->decimal('advance_amount', 18, 2)->default(0)
                ->comment('Tổng tạm ứng');
            $table->decimal('deduction_amount', 18, 2)->default(0)
                ->comment('Tổng khấu trừ');

            // Tổng kết lương
            $table->decimal('gross_salary', 18, 2)->default(0);
            $table->decimal('net_salary', 18, 2)->default(0);
            $table->text('note')->nullable();

            $table->unique(['payroll_id', 'driver_id'], 'payroll_driver_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
