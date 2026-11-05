<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_types', function (Blueprint $table): void {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->decimal('tourism_wage_rate', 8, 4)->default(0);
        });

        foreach ([4, 7, 16, 29, 34, 45] as $seats) {
            DB::table('vehicle_types')->insert([
                'code' => 'Xe'.str_pad((string) $seats, 2, '0', STR_PAD_LEFT),
                'name' => 'Xe '.$seats.' chỗ',
                'tourism_wage_rate' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('vehicles', function (Blueprint $table): void {
            $table->foreignId('vehicle_type_id')->nullable()->after('id')->constrained('vehicle_types')->nullOnDelete();
            $table->foreignId('default_driver_id')->nullable()->after('vehicle_type_id')->constrained('drivers')->nullOnDelete();
        });
        Schema::table('worker_vehicles', function (Blueprint $table): void {
            $table->foreignId('vehicle_type_id')->nullable()->after('vehicle_id')->constrained('vehicle_types')->nullOnDelete();
        });
        Schema::table('tourist_vehicles', function (Blueprint $table): void {
            $table->foreignId('vehicle_type_id')->nullable()->after('vehicle_id')->constrained('vehicle_types')->nullOnDelete();
            $table->string('customer_name', 255)->nullable()->after('customer_id');
        });
        Schema::create('route_vehicle_type_rates', function (Blueprint $table): void {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();
            $table->foreignId('route_id')->constrained('routes')->cascadeOnDelete();
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types')->restrictOnDelete();
            $table->decimal('freight_amount', 18, 2);
            $table->decimal('driver_wage', 18, 2);
            $table->unique(['route_id', 'vehicle_type_id']);
        });

        // Only exact seat counts are safe to map automatically.  Other records
        // intentionally stay unassigned and must be reviewed in the UI.
        DB::table('vehicles')->orderBy('id')->each(function (object $vehicle): void {
            $typeId = DB::table('vehicle_types')->where('code', 'Xe'.str_pad((string) $vehicle->seat_count, 2, '0', STR_PAD_LEFT))->value('id');
            if ($typeId) {
                DB::table('vehicles')->where('id', $vehicle->id)->update(['vehicle_type_id' => $typeId]);
            }
        });
        DB::table('tourist_vehicles')->whereNull('customer_name')->orderBy('id')->each(function (object $run): void {
            $name = $run->customer_id ? DB::table('customers')->where('id', $run->customer_id)->value('name') : null;
            DB::table('tourist_vehicles')->where('id', $run->id)->update(['customer_name' => $name]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_vehicle_type_rates');
        Schema::table('tourist_vehicles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('vehicle_type_id');
            $table->dropColumn('customer_name');
        });
        Schema::table('worker_vehicles', fn (Blueprint $table) => $table->dropConstrainedForeignId('vehicle_type_id'));
        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('vehicle_type_id');
            $table->dropConstrainedForeignId('default_driver_id');
        });
        Schema::dropIfExists('vehicle_types');
    }
};
