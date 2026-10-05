<?php

use App\Shared\Enums\DriverAdvanceStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Normalize the status domain while retaining existing confirmed advances.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $intermediateValues = [
            'pending',
            'confirmed',
            'payroll_locked',
            ...DriverAdvanceStatusEnum::values(),
        ];

        $this->modifyStatusColumn($intermediateValues);

        DB::table('driver_advances')
            ->where('status', 'confirmed')
            ->update(['status' => DriverAdvanceStatusEnum::APPROVED->value]);

        $this->modifyStatusColumn(DriverAdvanceStatusEnum::values());
    }

    /**
     * This data migration is intentionally forward-only: approved, paid, and
     * cancelled values cannot be converted back to attendance statuses safely.
     */
    public function down(): void
    {
        // Intentionally irreversible to avoid corrupting normalized data.
    }

    /**
     * @param  list<string>  $values
     */
    private function modifyStatusColumn(array $values): void
    {
        $quotedValues = implode(', ', array_map(
            static fn (string $value): string => "'{$value}'",
            array_values(array_unique($values)),
        ));

        DB::statement(
            "ALTER TABLE `driver_advances` MODIFY `status` ENUM({$quotedValues}) NOT NULL DEFAULT 'pending'",
        );
    }
};
