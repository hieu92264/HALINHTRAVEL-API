<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table): void {
            $table->string('license_number', 50)->nullable()->change();
            $table->string('license_class', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Historical rows may have been created without these legacy fields.
        // They are intentionally not made required again on rollback.
    }
};
