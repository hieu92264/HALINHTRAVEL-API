<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->string('recipient_name', 255)->nullable()->after('payment_method');
            $table->string('hired_driver_name', 255)->nullable()->after('recipient_name');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropColumn(['recipient_name', 'hired_driver_name']);
        });
    }
};
