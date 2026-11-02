<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_vehicle_schedule_rules', function (Blueprint $table): void {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('route_id')->constrained('routes')->restrictOnDelete();
            $table->string('shift_name', 100);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('pickup_location', 500);
            $table->string('dropoff_location', 500);
            $table->boolean('is_company_vehicle');
            $table->foreignId('partner_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->string('license_plate', 50);
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->boolean('is_hired_driver')->default(false);
            $table->string('hired_driver_name', 255)->nullable();
            $table->decimal('hired_driver_cost', 18, 2)->default(0);
            $table->text('note')->nullable();
            $table->index(['is_active', 'effective_from', 'effective_to'], 'worker_rules_active_period_index');
        });

        Schema::create('worker_vehicle_schedule_rule_days', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('schedule_rule_id')->constrained('worker_vehicle_schedule_rules')->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->time('pickup_time');
            $table->time('return_time')->nullable();
            $table->unique(['schedule_rule_id', 'weekday', 'pickup_time'], 'worker_rule_day_unique');
        });

        Schema::create('worker_vehicle_rates', function (Blueprint $table): void {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();
            $table->foreignId('route_id')->constrained('routes')->restrictOnDelete();
            $table->string('license_plate', 50);
            $table->decimal('freight_amount', 18, 2);
            $table->decimal('driver_wage', 18, 2);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->index(['route_id', 'license_plate', 'effective_from'], 'worker_rate_lookup_index');
        });

        foreach (['worker_vehicles', 'tourist_vehicles'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) use ($tableName): void {
                $table->id();
                $table->metadataColumns();
                $table->timestamps();
                $table->string('run_no', 50)->unique();
                if ($tableName === 'worker_vehicles') {
                    $table->foreignId('schedule_rule_id')->nullable()->constrained('worker_vehicle_schedule_rules')->nullOnDelete();
                    $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
                    $table->foreignId('route_id')->constrained('routes')->restrictOnDelete();
                    $table->string('shift_name', 100);
                } else {
                    $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                    $table->string('journey', 1000);
                    $table->decimal('driver_wage_rate', 8, 4)->default(0);
                    $table->decimal('driver_collected_amount', 18, 2)->default(0);
                    $table->decimal('company_collected_amount', 18, 2)->default(0);
                    $table->decimal('driver_remitted_amount', 18, 2)->default(0);
                    $table->decimal('driver_expense_amount', 18, 2)->default(0);
                }
                $table->date('run_date');
                $table->date('return_date')->nullable();
                $table->time('pickup_time');
                $table->time('return_time')->nullable();
                $table->string('pickup_location', 500);
                $table->string('dropoff_location', 500);
                $table->boolean('is_company_vehicle');
                $table->foreignId('partner_id')->nullable()->constrained('partners')->nullOnDelete();
                $table->string('license_plate', 50);
                $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
                $table->boolean('is_hired_driver')->default(false);
                $table->string('hired_driver_name', 255)->nullable();
                $table->decimal('hired_driver_cost', 18, 2)->default(0);
                $table->decimal('partner_vehicle_cost', 18, 2)->default(0);
                $table->decimal('freight_amount', 18, 2)->default(0);
                $table->decimal('driver_wage', 18, 2)->default(0);
                $table->string('status', 30)->default('planned');
                $table->timestamp('completed_at')->nullable();
                $table->text('note')->nullable();
                $table->index(['run_date', 'status'], $tableName.'_date_status_index');
                $table->index(['license_plate', 'run_date'], $tableName.'_plate_date_index');
                $table->index(['driver_id', 'run_date'], $tableName.'_driver_date_index');
            });
        }

        Schema::table('worker_vehicles', function (Blueprint $table): void {
            $table->unique(['schedule_rule_id', 'run_date', 'pickup_time'], 'worker_run_rule_date_time_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tourist_vehicles');
        Schema::dropIfExists('worker_vehicles');
        Schema::dropIfExists('worker_vehicle_rates');
        Schema::dropIfExists('worker_vehicle_schedule_rule_days');
        Schema::dropIfExists('worker_vehicle_schedule_rules');
    }
};
