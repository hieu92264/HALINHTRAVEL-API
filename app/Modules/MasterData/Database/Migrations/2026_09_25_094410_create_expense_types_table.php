<?php

use App\Shared\Enums\ExpenseTypeEnum;
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
        Schema::create('expense_types', function (Blueprint $table) {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();
            $table->string('code', 50)->unique()->comment('Ví dụ: FUEL'); // VARCHAR(50) UNIQUE[cite: 8]
            $table->string('name', 150)->comment('Ví dụ: Xăng dầu'); // VARCHAR(150)[cite: 8]
            $table->enum('scope', ExpenseTypeEnum::values())->comment('Phạm vi: vehicle / trip / general');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_types');
    }
};
