<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rename the legacy password column without affecting fresh installations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('users', 'password_hash') && ! Schema::hasColumn('users', 'password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('password_hash', 'password');
            });
        }
    }

    /**
     * Restore the legacy column name when rolling back this migration.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'password') && ! Schema::hasColumn('users', 'password_hash')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('password', 'password_hash');
            });
        }
    }
};
