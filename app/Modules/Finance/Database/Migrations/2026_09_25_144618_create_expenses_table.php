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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();

            $table->string('expense_no', 50)->unique()->comment('Ví dụ: PC20260001');
            $table->foreignId('expense_type_id')->constrained('expense_types')->restrictOnDelete();
            $table->enum('scope', \App\Shared\Enums\ExpenseTypeEnum::values())
                ->comment('vehicle / trip / general');

            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete(); // BIGINT UNSIGNED FK NULL[cite: 23]
            $table->foreignId('dispatch_order_id')->nullable()->constrained('dispatch_orders')->nullOnDelete(); // BIGINT UNSIGNED FK NULL[cite: 23]
            $table->foreignId('partner_id')->nullable()->constrained('partners')->nullOnDelete()->comment('Gara / cây xăng...'); // BIGINT UNSIGNED FK NULL[cite: 23]
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete()->comment('Người chi / ứng chi');

            // Chi tiết chi phí
            $table->dateTime('expense_date'); // DATETIME[cite: 23]
            $table->decimal('amount', 18, 2)->comment('Số lượng'); // DECIMAL(18,2)[cite: 23]
            $table->string('payment_method', 30)->nullable(); // VARCHAR(30) NULL[cite: 23]
            $table->string('document_no', 100)->nullable()->comment('Số hóa đơn/chứng từ'); // VARCHAR(100) NULL[cite: 23]
            $table->string('description', 500)->nullable();

            $table->boolean('is_locked')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
