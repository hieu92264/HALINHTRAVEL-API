<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table): void {
            $table->dateTime('customer_responded_at')->nullable()->after('approved_at');
            $table->text('customer_response_note')->nullable()->after('customer_responded_at');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table): void {
            $table->dropColumn(['customer_responded_at', 'customer_response_note']);
        });
    }
};
