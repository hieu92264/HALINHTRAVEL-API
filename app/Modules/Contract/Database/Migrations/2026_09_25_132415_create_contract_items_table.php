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
        Schema::create('contract_items', function (Blueprint $table) {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();

            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('route_id')
                ->nullable()
                ->constrained('routes')
                ->nullOnDelete();
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types')->restrictOnDelete();

            // Thông tin dịch vụ
            $table->enum('service_type', \App\Shared\Enums\RentalServiceTypeEnum::values())->comment('fixed / tourism / school / business');
            $table->unsignedInteger('quantity')->default(1);

            // Chi phí & Lương
            $table->decimal('unit_price', 18, 2)->comment('Đơn giá dịch vụ');
            $table->decimal('driver_wage', 18, 2)->default(0)->comment('Lương trả cho tài xế sau mỗi chuyến đi');

            // Địa điểm & Ghi chú
            $table->string('pickup_location', 500)->nullable();
            $table->string('dropoff_location', 500)->nullable();
            $table->text('note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_items');
    }
};
