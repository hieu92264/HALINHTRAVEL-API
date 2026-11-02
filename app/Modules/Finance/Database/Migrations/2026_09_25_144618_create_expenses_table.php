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
            // Phiếu chi chung độc lập.
            $table->dateTime('expense_date'); // DATETIME[cite: 23]
            $table->decimal('amount', 18, 2)->comment('Số lượng'); // DECIMAL(18,2)[cite: 23]
            $table->string('payment_method', 30)->nullable(); // VARCHAR(30) NULL[cite: 23]
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
