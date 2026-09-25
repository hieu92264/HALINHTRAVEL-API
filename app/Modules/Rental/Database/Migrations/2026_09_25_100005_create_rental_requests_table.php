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
        Schema::create('rental_requests', function (Blueprint $table) {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();

            // Thông tin yêu cầu
            $table->string('request_no', 50)->unique()->comment('Ví dụ: YC20260001');
            $table->foreignId('customer_id')->constrained('customers')
                ->restrictOnDelete();
            $table->string('source', 30)->nullable()->comment('Nguồn: phone / zalo / facebook / direct');
            $table->dateTime('requested_at');

            // Chi tiết dịch vụ
            $table->enum('service_type', \App\Shared\Enums\RentalServiceTypeEnum::values())->comment('Loại dịch vụ: fixed / tourism / school / business');
            $table->string('pickup_location', 500)->nullable(); // VARCHAR(500) NULL[cite: 9]
            $table->string('dropoff_location', 500)->nullable(); // VARCHAR(500) NULL[cite: 9]
            $table->dateTime('start_at')->nullable(); // DATETIME NULL[cite: 9]
            $table->dateTime('end_at')->nullable();

            // Ghi chú và Trạng thái
            $table->text('note')->nullable(); // TEXT NULL[cite: 9]
            $table->enum('status', \App\Shared\Enums\RentalRequestStatusEnum::values())
                ->default(\App\Shared\Enums\RentalRequestStatusEnum::NEW)
                ->comment('Trạng thái: new / quoted / accepted / rejected / converted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_requests');
    }
};
