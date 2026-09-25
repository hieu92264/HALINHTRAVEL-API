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
        Schema::create('route_rates', function (Blueprint $table) {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();

            $table->foreignId('route_id')->constrained('routes')->cascadeOnDelete();
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types')->cascadeOnDelete();

            // Giá cước & Lương
            $table->decimal('customer_price', 18, 2)->comment('Cước thu khách'); // DECIMAL(18,2)[cite: 8]
            $table->decimal('driver_wage', 18, 2)->default(0)->comment('Lương/chuyến tuyến cố định');

            // Hiệu lực
            $table->date('effective_from'); // DATE[cite: 8]
            $table->date('effective_to')->nullable();

            $table->unique(['route_id', 'vehicle_type_id', 'effective_from'], 'route_vehicle_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('route_rates');
    }
};
