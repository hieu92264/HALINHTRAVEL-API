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
        Schema::create('partner_payments', function (Blueprint $table) {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();

            $table->string('payment_no', 50)->unique()->comment('Ví dụ: CTDT20260001');
            $table->foreignId('partner_id')->constrained('partners')->restrictOnDelete();
            $table->foreignId('dispatch_order_id')->nullable()->constrained('dispatch_orders')
                ->restrictOnDelete()
                ->comment('Có thể thanh toán theo chuyến');

            // Chi tiết thanh toán
            $table->dateTime('paid_at'); // DATETIME[cite: 17]
            $table->decimal('amount', 18, 2); // DECIMAL(18,2)[cite: 17]
            $table->enum('payment_method', \App\Shared\Enums\PaymentMethodEnum::values())->comment('cash / bank_transfer');
            $table->string('description', 500)->nullable();

            // Trạng thái khóa sổ
            $table->boolean('is_locked')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_payments');
    }
};
