<?php

namespace Database\Seeders;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Shared\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use LogicException;
use RuntimeException;

/**
 * Imports the approved local MySQL snapshot without executing its DDL.
 *
 * The source SQL file is deliberately Git-ignored because it contains PII and
 * financial data. This seeder maps source primary keys to the target database's
 * generated IDs, so it is safe to use on a freshly migrated database.
 */
class ImportedHalinhTravelSeeder extends Seeder
{
    /** @var array<string, array<int, int>> */
    private array $sourceIds = [];

    private HalinhTravelSqlDump $dump;

    /** @var array<string, string> */
    private const FOREIGN_TABLES = [
        'customer_id' => 'customers',
        'partner_id' => 'partners',
        'vehicle_type_id' => 'vehicle_types',
        'vehicle_id' => 'vehicles',
        'driver_id' => 'drivers',
        'expense_type_id' => 'expense_types',
        'route_id' => 'routes',
        'rental_request_id' => 'rental_requests',
        'quotation_id' => 'quotations',
        'contract_id' => 'contracts',
        'contract_item_id' => 'contract_items',
        'schedule_rule_id' => 'contract_schedule_rules',
        'default_vehicle_id' => 'vehicles',
        'default_driver_id' => 'drivers',
        'required_vehicle_type_id' => 'vehicle_types',
        'trip_schedule_id' => 'trip_schedules',
        'trip_assignment_id' => 'trip_assignments',
        'replaced_assignment_id' => 'trip_assignments',
        'dispatch_order_id' => 'dispatch_orders',
        'driver_attendance_id' => 'driver_attendances',
        'payroll_id' => 'payrolls',
        'payroll_item_id' => 'payroll_items',
    ];

    /** @var list<string> */
    public const SNAPSHOT_TABLES = [
        'customers',
        'partners',
        'vehicle_types',
        'vehicles',
        'drivers',
        'expense_types',
        'routes',
        'route_rates',
        'rental_requests',
        'rental_request_items',
        'quotations',
        'quotation_items',
        'contracts',
        'contract_items',
        'contract_schedule_rules',
        'contract_schedule_days',
        'trip_schedules',
        'trip_assignments',
        'dispatch_orders',
        'driver_attendances',
        'driver_advances',
        'payrolls',
        'payroll_items',
        'payroll_item_details',
        'receipts',
        'expenses',
        'partner_payments',
    ];

    public static function isAvailable(): bool
    {
        return File::isFile(self::snapshotPath());
    }

    public function run(): void
    {
        $path = self::snapshotPath();

        if (! File::isFile($path)) {
            throw new RuntimeException("Không tìm thấy file dữ liệu local: {$path}");
        }

        $this->dump = new HalinhTravelSqlDump(File::get($path));
        $this->assertDatabaseCanReceiveSnapshot();

        DB::transaction(function (): void {
            $this->call(AuthDatabaseSeeder::class);
            $this->seedUsers();
            $this->seedBusinessData();
        });
    }

    private static function snapshotPath(): string
    {
        return (string) config('halinh_travel_import.sql_path');
    }

    private function assertDatabaseCanReceiveSnapshot(): void
    {
        if (! DB::table('customers')->exists()) {
            return;
        }

        if (DB::table('customers')->where('code', 'ALUMINIUM')->exists()) {
            return;
        }

        throw new LogicException(
            'Local snapshot chỉ được seed vào database trống hoặc database đã có snapshot Halinh Travel.',
        );
    }

    private function seedUsers(): void
    {
        $rolesByUserId = [];

        foreach ($this->dump->rows('model_has_roles') as $assignment) {
            if ($assignment['model_type'] === User::class) {
                $rolesByUserId[(int) $assignment['model_id']] = (int) $assignment['role_id'];
            }
        }

        foreach ($this->dump->rows('users') as $sourceUser) {
            $user = User::query()->updateOrCreate(
                ['user_name' => $sourceUser['user_name']],
                [
                    'email' => $sourceUser['email'],
                    'password' => 'password',
                    'last_login_at' => $sourceUser['last_login_at'],
                    'email_verified_at' => $sourceUser['email_verified_at'],
                ],
            );

            $user->forceFill([
                'is_active' => $this->toBoolean($sourceUser['is_active']),
                'user_name_created' => $sourceUser['user_name_created'],
                'user_name_updated' => $sourceUser['user_name_updated'],
                'created_at' => $sourceUser['created_at'],
                'updated_at' => $sourceUser['updated_at'],
            ])->save();

            $sourceRoleId = $rolesByUserId[(int) $sourceUser['id']] ?? null;
            $role = match ($sourceRoleId) {
                1 => RoleEnum::ADMIN,
                2 => RoleEnum::DIRECTOR,
                3 => RoleEnum::SALES,
                4 => RoleEnum::DISPATCHER,
                5 => RoleEnum::ACCOUNTANT,
                default => RoleEnum::DRIVER,
            };

            $user->syncRoles([$role->value]);
        }
    }

    private function seedBusinessData(): void
    {
        $this->seedTable('customers', ['code']);
        $this->seedTable('partners', ['code']);
        $this->seedTable('vehicle_types', ['code']);
        $this->seedTable('vehicles', ['license_plate']);
        $this->seedTable('drivers', ['code']);
        $this->seedTable('expense_types', ['code']);
        $this->seedTable('routes', ['code']);
        $this->seedTable('route_rates', ['route_id', 'vehicle_type_id', 'effective_from']);

        $this->seedTable('rental_requests', ['request_no'], function (array $row): array {
            if (in_array((int) $row['id'], $this->contractRentalRequestIds(), true)) {
                $row['status'] = 'converted';
            }

            return $row;
        });
        $this->seedTable('rental_request_items', ['rental_request_id', 'vehicle_type_id', 'route_id']);
        $this->seedTable('quotations', ['quotation_no'], function (array $row): array {
            // The supplied dump has one quotation per request, in matching source order.
            $row['rental_request_id'] = $row['id'];

            return $row;
        });
        $this->seedTable('quotation_items', ['quotation_id', 'route_id', 'vehicle_type_id', 'description']);

        $this->seedTable('contracts', ['contract_no']);
        $this->seedTable('contract_items', ['contract_id', 'route_id', 'vehicle_type_id']);
        $this->seedTable('contract_schedule_rules', ['contract_item_id', 'route_id', 'effective_from', 'effective_to']);
        $this->seedTable('contract_schedule_days', ['schedule_rule_id', 'weekday', 'pickup_time']);

        $this->seedTable('trip_schedules', ['schedule_no']);
        $this->seedTable('trip_assignments', ['trip_schedule_id', 'assignment_type']);
        $this->seedTable('dispatch_orders', ['order_no']);

        $this->seedTable('driver_attendances', ['dispatch_order_id']);
        $this->seedTable('driver_advances', ['advance_no'], static function (array $row): array {
            // The current workflow calls an approved advance "confirmed".
            $row['status'] = $row['status'] === 'approved' ? 'confirmed' : $row['status'];

            return $row;
        });

        $this->seedTable('payrolls', ['code']);
        $this->seedTable('payroll_items', ['payroll_id', 'driver_id']);
        $this->seedZeroPayrollItems();
        $this->seedTable('payroll_item_details', [
            'payroll_item_id',
            'driver_attendance_id',
            'dispatch_order_id',
            'calculation_type',
        ]);

        $this->seedTable('receipts', ['receipt_no']);
        $this->seedTable('expenses', ['expense_no']);
        $this->seedTable('partner_payments', ['payment_no']);
    }

    /**
     * @param  list<string>  $uniqueColumns
     * @param  (callable(array<string, mixed>): array<string, mixed>)|null  $normalizer
     */
    private function seedTable(string $table, array $uniqueColumns, ?callable $normalizer = null): void
    {
        foreach ($this->dump->rows($table) as $sourceRow) {
            $row = $normalizer === null ? $sourceRow : $normalizer($sourceRow);
            $row = $this->mapForeignKeys($row);
            $sourceId = (int) $sourceRow['id'];
            unset($row['id']);

            $uniqueValues = array_intersect_key($row, array_flip($uniqueColumns));
            DB::table($table)->updateOrInsert($uniqueValues, $row);

            $targetId = DB::table($table)->where($uniqueValues)->value('id');

            if (! is_int($targetId) && ! ctype_digit((string) $targetId)) {
                throw new RuntimeException("Không thể xác định ID đích cho bảng {$table}.");
            }

            $this->sourceIds[$table][$sourceId] = (int) $targetId;
        }
    }

    private function seedZeroPayrollItems(): void
    {
        $zeroAmountColumns = [
            'base_salary',
            'responsibility_allowance',
            'meal_allowance',
            'fixed_trip_wage',
            'tourism_commission',
            'other_allowance',
            'advance_amount',
            'deduction_amount',
            'gross_salary',
            'net_salary',
        ];

        foreach ($this->dump->rows('payrolls') as $payroll) {
            if (! in_array((int) $payroll['month'], [6, 7, 8, 9], true)) {
                continue;
            }

            foreach ($this->dump->rows('drivers') as $driver) {
                $values = array_fill_keys($zeroAmountColumns, '0.00');
                $values = [
                    ...$values,
                    'is_active' => true,
                    'user_name_created' => 'admin',
                    'user_name_updated' => 'admin',
                    'created_at' => $payroll['created_at'],
                    'updated_at' => $payroll['updated_at'],
                    'payroll_id' => $this->sourceId('payrolls', (int) $payroll['id']),
                    'driver_id' => $this->sourceId('drivers', (int) $driver['id']),
                    'note' => 'Không có chi tiết lương trong snapshot nguồn; đã tạo dòng 0.00 theo quy ước import.',
                ];

                DB::table('payroll_items')->updateOrInsert(
                    [
                        'payroll_id' => $values['payroll_id'],
                        'driver_id' => $values['driver_id'],
                    ],
                    $values,
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function mapForeignKeys(array $row): array
    {
        foreach (self::FOREIGN_TABLES as $column => $sourceTable) {
            if (! array_key_exists($column, $row) || $row[$column] === null) {
                continue;
            }

            $row[$column] = $this->sourceId($sourceTable, (int) $row[$column]);
        }

        return $row;
    }

    private function sourceId(string $table, int $sourceId): int
    {
        $targetId = $this->sourceIds[$table][$sourceId] ?? null;

        if ($targetId === null) {
            throw new RuntimeException("Thiếu quan hệ {$table}.{$sourceId} trong snapshot import.");
        }

        return $targetId;
    }

    /** @return list<int> */
    private function contractRentalRequestIds(): array
    {
        return array_map(
            static fn (array $contract): int => (int) $contract['rental_request_id'],
            array_filter(
                $this->dump->rows('contracts'),
                static fn (array $contract): bool => $contract['rental_request_id'] !== null,
            ),
        );
    }

    private function toBoolean(mixed $value): bool
    {
        return in_array($value, [true, 1, '1'], true);
    }
}

/**
 * Small parser for Navicat's one-row-per-INSERT MySQL export format.
 *
 * It reads only column definitions and INSERT statements; DDL from the dump is
 * never executed.
 */
class HalinhTravelSqlDump
{
    /** @var array<string, list<string>> */
    private array $columns = [];

    /** @var array<string, list<array<string, mixed>>> */
    private array $rows = [];

    public function __construct(string $sql)
    {
        $this->parseColumns($sql);
        $this->parseRows($sql);
    }

    /** @return list<array<string, mixed>> */
    public function rows(string $table): array
    {
        return $this->rows[$table] ?? [];
    }

    private function parseColumns(string $sql): void
    {
        preg_match_all(
            '/CREATE TABLE `(?<table>[^`]+)`\s*\((?<definition>.*?)\) ENGINE/s',
            $sql,
            $tables,
            PREG_SET_ORDER,
        );

        foreach ($tables as $table) {
            preg_match_all('/^\s*`(?<column>[^`]+)`\s+/m', $table['definition'], $columns);
            $this->columns[$table['table']] = $columns['column'];
        }
    }

    private function parseRows(string $sql): void
    {
        preg_match_all(
            '/^INSERT INTO `(?<table>[^`]+)` VALUES \((?<values>.*)\);\s*$/m',
            $sql,
            $inserts,
            PREG_SET_ORDER,
        );

        foreach ($inserts as $insert) {
            $table = $insert['table'];

            if (! in_array($table, ImportedHalinhTravelSeeder::SNAPSHOT_TABLES, true)
                && ! in_array($table, ['users', 'model_has_roles'], true)) {
                continue;
            }

            $columns = $this->columns[$table] ?? [];

            if ($columns === []) {
                throw new RuntimeException("Không đọc được cấu trúc bảng {$table} từ file SQL.");
            }

            $values = $this->parseValues($insert['values']);

            if (count($columns) !== count($values)) {
                throw new RuntimeException("Số cột và giá trị không khớp trong bảng {$table}.");
            }

            $this->rows[$table][] = array_combine($columns, $values);
        }
    }

    /** @return list<string|null> */
    private function parseValues(string $values): array
    {
        $parsed = [];
        $value = '';
        $inString = false;
        $length = strlen($values);

        for ($index = 0; $index < $length; $index++) {
            $character = $values[$index];

            if ($inString && $character === '\\' && $index + 1 < $length) {
                $next = $values[++$index];
                $value .= match ($next) {
                    '0' => "\0",
                    'b' => "\b",
                    'n' => "\n",
                    'r' => "\r",
                    't' => "\t",
                    'Z' => "\x1a",
                    default => $next,
                };

                continue;
            }

            if ($character === "'") {
                $inString = ! $inString;

                continue;
            }

            if ($character === ',' && ! $inString) {
                $parsed[] = $this->parseValue($value);
                $value = '';

                continue;
            }

            $value .= $character;
        }

        $parsed[] = $this->parseValue($value);

        return $parsed;
    }

    private function parseValue(string $value): ?string
    {
        $value = trim($value);

        return strtoupper($value) === 'NULL' ? null : $value;
    }
}
