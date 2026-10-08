<?php

use App\Shared\Enums\DispatchOrderStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_orders', function (Blueprint $table): void {
            $table->dropUnique(['trip_schedule_id']);
            $table->index('trip_schedule_id');
            $table->enum('status', DispatchOrderStatusEnum::values())->change();
            $table->dateTime('reported_at')->nullable()->after('completed_at');
            $table->string('reported_by', 100)->nullable()->after('reported_at');
            $table->dateTime('confirmed_at')->nullable()->after('reported_by');
            $table->string('confirmed_by', 100)->nullable()->after('confirmed_at');
            $table->text('review_note')->nullable()->after('confirmed_by');
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_orders', function (Blueprint $table): void {
            $table->dropColumn(['reported_at', 'reported_by', 'confirmed_at', 'confirmed_by', 'review_note']);
            $table->enum('status', ['ISSUED', 'ASSIGNED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'])->change();
            $table->dropIndex(['trip_schedule_id']);
            $table->unique('trip_schedule_id');
        });
    }
};
