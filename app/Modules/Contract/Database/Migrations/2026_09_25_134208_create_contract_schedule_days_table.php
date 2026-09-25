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
        Schema::create('contract_schedule_days', function (Blueprint $table) {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();

            $table->foreignId('schedule_rule_id')
                ->constrained('contract_schedule_rules')
                ->cascadeOnDelete()
                ->comment('Tham chiếu contract_schedule_rules.id');

            $table->enum('weekday', \App\Shared\Enums\WeekdayEnum::values())
                ->comment('Ngày trong tuần, ví dụ: Mon, Tue, Wed, Thu, Fri, Sat, Sun');
            $table->time('pickup_time');
            $table->time('return_time')->nullable();
            $table->string('shift_name', 100)->nullable()->comment('Ví dụ: Ca sáng');
            $table->unique(['schedule_rule_id', 'weekday', 'pickup_time'], 'schedule_rule_weekday_time_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_schedule_days');
    }
};
