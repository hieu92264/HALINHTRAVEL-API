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
        Schema::create('rental_request_items', function (Blueprint $table) {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();

            $table->foreignId('rental_request_id')->constrained('rental_requests')->cascadeOnDelete();
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types')->restrictOnDelete();

            // Chi tiết số lượng & Ghi chú
            $table->unsignedInteger('quantity')->default(1); // INT UNSIGNED DEFAULT 1[cite: 10]
            $table->foreignId('route_id')->nullable()->constrained('routes')->nullOnDelete(); // BIGINT UNSIGNED FK NULL[cite: 10]
            $table->text('note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_request_items');
    }
};
