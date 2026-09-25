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
        Schema::create('contract_schedule_rules', function (Blueprint $table) {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();

            $table->foreignId('contract_item_id')->constrained('contract_items')->cascadeOnDelete();
            $table->foreignId('route_id')->nullable()
                ->constrained('routes')
                ->nullOnDelete();

            // Thời gian hiệu lực
            $table->date('effective_from');
            $table->date('effective_to');

            // Cấu hình mặc định (Xe cứng & Tài xế cứng)
            $table->foreignId('default_vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete()->comment('Xe cứng');
            $table->foreignId('default_driver_id')->nullable()->constrained('drivers')->nullOnDelete()->comment('Tài xế cứng');
            $table->text('note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_schedule_rules');
    }
};
