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
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();

            // Thông tin định danh & Liên kết
            $table->string('quotation_no', 50)->unique()->comment('Ví dụ: BG20260001');
            $table->foreignId('rental_request_id')->nullable()->constrained('rental_requests')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();

            // Thời gian
            $table->date('quotation_date'); // DATE[cite: 11]
            $table->date('valid_until')->nullable();

            // Chi phí
            $table->decimal('subtotal', 18, 2)->default(0); // DECIMAL(18,2) DEFAULT 0[cite: 11]
            $table->decimal('discount_amount', 18, 2)->default(0); // DECIMAL(18,2) DEFAULT 0[cite: 11]
            $table->decimal('total_amount', 18, 2)->default(0);

            // Điều khoản & Trạng thái
            $table->text('payment_terms')->nullable(); // TEXT NULL[cite: 11]
            $table->enum('status', \App\Shared\Enums\QuotationStatusEnum::values())
                ->default(\App\Shared\Enums\QuotationStatusEnum::DRAFT)
                ->comment('draft / sent / approved / rejected / expired');

            // Thông tin duyệt & Người tạo
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
