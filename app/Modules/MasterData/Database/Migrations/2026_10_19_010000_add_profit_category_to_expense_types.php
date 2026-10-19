<?php

use App\Shared\Enums\ProfitCategoryEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_types', function (Blueprint $table): void {
            $table->enum('profit_category', ProfitCategoryEnum::values())->nullable()->after('scope');
        });
    }

    public function down(): void
    {
        Schema::table('expense_types', function (Blueprint $table): void {
            $table->dropColumn('profit_category');
        });
    }
};
