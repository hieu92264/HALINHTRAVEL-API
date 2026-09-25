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
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();

            // Thông tin định danh tuyến
            $table->string('code', 50)->unique()->comment('Ví dụ: TUYEN001');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete()
                ->comment('Tuyến riêng của khách nếu có');

            // Chi tiết tuyến đường
            $table->string('name', 255)->comment('Ví dụ: VSIP - Thủy Nguyên'); // VARCHAR(255)[cite: 7]
            $table->string('shift_name', 100)->nullable()->comment('Ví dụ: Ca sáng'); // VARCHAR(100) NULL[cite: 7]
            $table->string('pickup_location', 500); // VARCHAR(500)[cite: 7]
            $table->string('dropoff_location', 500);

            // Thời gian & Khoảng cách
            $table->time('default_pickup_time')->nullable(); // TIME NULL[cite: 7]
            $table->time('default_return_time')->nullable(); // TIME NULL[cite: 7]
            $table->decimal('estimated_distance_km', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
