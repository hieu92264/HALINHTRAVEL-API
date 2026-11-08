<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payrolls MODIFY status ENUM('draft','calculated','approved','paid','locked','superseded','cancelled') NOT NULL DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('payrolls')->where('status', 'cancelled')->update(['status' => 'superseded']);
            DB::statement("ALTER TABLE payrolls MODIFY status ENUM('draft','calculated','approved','paid','locked','superseded') NOT NULL DEFAULT 'draft'");
        }
    }
};
