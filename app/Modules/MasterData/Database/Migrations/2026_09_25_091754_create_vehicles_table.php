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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();

            // Thông tin định danh & Phân loại
            $table->string('license_plate', 20)->unique()->comment('Ví dụ: 15B-123.45'); // VARCHAR(20) UNIQUE[cite: 5]
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types')->restrictOnDelete();

            // Thông tin sở hữu
            $table->enum('ownership_type', \App\Shared\Enums\OwnershipTypeEnum::values())
                ->comment('Loại sở hữu: đối tác/ công ty');
            $table->foreignId('partner_id')->nullable()->constrained('partners')
                ->nullOnDelete()
                ->comment('Bắt buộc khi ownership_type=partner');

            // Chi tiết xe
            $table->string('brand', 100)->nullable()->comment('Ví dụ: Ford'); // VARCHAR(100) NULL[cite: 5]
            $table->string('model', 100)->nullable()->comment('Ví dụ: Transit'); // VARCHAR(100) NULL[cite: 5]
            $table->smallInteger('manufacture_year')->nullable()->comment('Năm sản xuất, VD: 2024'); // SMALLINT NULL[cite: 5]
            $table->unsignedInteger('current_odometer')->nullable()->comment('Số km hiện tại');

            // Trạng thái & Ghi chú
            $table->enum('vehicle_status', \App\Shared\Enums\VehicleStatusEnum::values())->comment('available / assigned / maintenance / inactive'); // VARCHAR(30)[cite: 5]
            $table->text('notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
