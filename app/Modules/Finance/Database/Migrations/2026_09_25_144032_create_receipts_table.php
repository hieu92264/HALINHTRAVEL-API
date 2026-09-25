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
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();

            $table->string('receipt_no', 50)->unique()->comment('Ví dụ: PT20260001');
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->restrictOnDelete();

            // Chi tiết phiếu thu
            $table->enum('receipt_type', \App\Shared\Enums\ReceiptTypeEnum::values())
                ->comment('deposit / contract_payment / other');
            $table->dateTime('received_at');
            $table->decimal('amount', 18, 2);
            $table->enum('payment_method', \App\Shared\Enums\PaymentMethodEnum::values())
                ->comment('cash / bank_transfer');

            // Thông tin người nộp & Ghi chú
            $table->string('payer_name', 255)->nullable();
            $table->string('description', 500)->nullable();

            // Trạng thái khóa sổ
            $table->boolean('is_locked')->default(false)->comment('Khóa sổ');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
