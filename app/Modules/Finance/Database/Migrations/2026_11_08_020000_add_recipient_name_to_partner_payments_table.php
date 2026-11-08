<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_payments', function (Blueprint $table) {
            $table->string('recipient_name', 255)->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('partner_payments', function (Blueprint $table) {
            $table->dropColumn('recipient_name');
        });
    }
};
