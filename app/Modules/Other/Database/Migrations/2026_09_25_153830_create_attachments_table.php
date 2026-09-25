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
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();

            $table->morphs('attachable');
            $table->string('file_name', 255); // VARCHAR(255)[cite: 22]
            $table->string('file_path', 1000); // VARCHAR(1000)[cite: 22]
            $table->string('mime_type', 100)->nullable(); // VARCHAR(100) NULL[cite: 22]
            $table->unsignedBigInteger('file_size')->nullable(); // BIGINT UNSIGNED NULL[cite: 22]

            // Người tải lên
            $table->string('uploaded_by', 100)->nullable();
            $table->foreign('uploaded_by')->references('user_name')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
