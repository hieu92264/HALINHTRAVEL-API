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
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();

            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->foreignId('route_id')->nullable()->constrained('routes')->nullOnDelete();
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types')->restrictOnDelete();

            // Chi tiết hạng mục
            $table->string('description', 500)->nullable();
            $table->unsignedInteger('quantity')->default(1);

            // Thành tiền
            $table->decimal('unit_price', 18, 2);
            $table->decimal('amount', 18, 2);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};
