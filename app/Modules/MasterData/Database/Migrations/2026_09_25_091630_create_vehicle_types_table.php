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
        Schema::create('vehicle_types', function (Blueprint $table) {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();

            // Thông tin cơ bản
            $table->string('code', 30)->unique()->comment('Ví dụ: XE16'); // Kiểu dữ liệu VARCHAR(30) UNIQUE[cite: 4]
            $table->string('name', 100)->comment('Ví dụ: Xe 16 chỗ'); // Kiểu dữ liệu VARCHAR(100)[cite: 4]
            $table->integer('seats')->comment('Ví dụ: 16'); // Kiểu dữ liệu INT[cite: 4]

            // Cấu hình tỷ lệ
            $table->decimal('tour_driver_commission_rate', 5, 2)->default(0)->comment('% lương tài xế chuyến du lịch'); // Kiểu dữ liệu DECIMAL(5,2) DEFAULT 0[cite: 4]
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_types');
    }
};
