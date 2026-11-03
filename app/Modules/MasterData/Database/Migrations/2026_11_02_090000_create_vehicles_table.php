<?php

use App\Shared\Enums\OwnershipTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();
            $table->string('code', 50)->unique();
            $table->string('license_plate', 50)->unique();
            $table->unsignedSmallInteger('seat_count');
            $table->string('vehicle_model', 255)->nullable();
            $table->enum('ownership_type', OwnershipTypeEnum::values());
            $table->foreignId('partner_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->text('note')->nullable();
            $table->index(['is_active', 'ownership_type'], 'vehicles_active_ownership_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
