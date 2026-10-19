<?php

use App\Shared\Enums\PaymentMethodEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_cash_remittances', function (Blueprint $table): void {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();
            $table->string('remittance_no', 50)->unique();
            $table->foreignId('receipt_id')->constrained('receipts')->restrictOnDelete();
            $table->foreignId('dispatch_order_id')->constrained('dispatch_orders')->restrictOnDelete();
            $table->foreignId('driver_id')->constrained('drivers')->restrictOnDelete();
            $table->dateTime('remitted_at');
            $table->decimal('amount', 18, 2);
            $table->enum('payment_method', PaymentMethodEnum::values());
            $table->string('description', 500)->nullable();
            $table->boolean('is_locked')->default(false);
            $table->index(['driver_id', 'remitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_cash_remittances');
    }
};
