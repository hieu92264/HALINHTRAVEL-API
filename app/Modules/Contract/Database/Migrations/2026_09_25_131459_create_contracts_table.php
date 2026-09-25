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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();

            // Thông tin định danh & Liên kết
            $table->string('contract_no', 50)->unique()->comment('Ví dụ: HD20260001');
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('rental_request_id')->nullable()->constrained('rental_requests')->nullOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained('quotations')->nullOnDelete();

            // Loại hợp đồng & Thời gian
            $table->enum('contract_type', \App\Shared\Enums\ContractTypeEnum::values())->comment('trip / principle');
            $table->date('signed_date')->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable()->comment('Hợp đồng chuyến có thể cùng ngày');

            // Tài chính & Điều khoản
            $table->decimal('total_amount', 18, 2)->default(0)->comment('Tổng giá trị hợp đồng');
            $table->decimal('deposit_required', 18, 2)->default(0)->comment('Tiền đặt cọc');
            $table->text('payment_terms')->nullable()->comment('Điều kiện thanh toán');
            $table->longText('terms')->nullable()->comment('Điều khoản');

            $table->enum('status', \App\Shared\Enums\ContractStatusEnum::values())->comment('draft / active / completed / cancelled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
