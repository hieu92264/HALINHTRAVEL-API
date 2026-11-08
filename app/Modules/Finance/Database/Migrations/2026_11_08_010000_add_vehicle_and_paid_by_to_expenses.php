<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->foreignId('vehicle_id')->nullable()->after('expense_type_id')->constrained('vehicles')->nullOnDelete();
            $table->string('paid_by', 255)->nullable()->after('recipient_name');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('vehicle_id');
            $table->dropColumn('paid_by');
        });
    }
};
