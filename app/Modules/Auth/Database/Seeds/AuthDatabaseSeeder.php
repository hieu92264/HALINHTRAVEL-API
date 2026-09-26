<?php

namespace App\Modules\Auth\Database\Seeds;

use App\Modules\Auth\Models\Permission as PermissionModel;
use App\Modules\Auth\Models\Role as RoleModel;
use App\Modules\Auth\Models\User;
use App\Shared\Enums\PermissionEnum;
use App\Shared\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class AuthDatabaseSeeder extends Seeder
{
    /**
     * Seed the Auth module's database records.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionEnum::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value, 'api');
        }

        $admin = RoleModel::findOrCreate(RoleEnum::ADMIN->value, 'api');
        $admin->syncPermissions(PermissionEnum::values());

        $director = RoleModel::findOrCreate(RoleEnum::DIRECTOR->value, 'api');
        $director->syncPermissions(array_merge(
            $this->viewPermissions(),
            $this->permissionValues([
                PermissionEnum::QUOTATIONS_MANAGE,
                PermissionEnum::CONTRACTS_MANAGE,
                PermissionEnum::RECEIPTS_MANAGE,
                PermissionEnum::EXPENSES_MANAGE,
                PermissionEnum::PARTNER_PAYMENTS_MANAGE,
                PermissionEnum::DRIVER_ADVANCES_MANAGE,
                PermissionEnum::PAYROLLS_MANAGE,
            ]),
        ));

        $sales = RoleModel::findOrCreate(RoleEnum::SALES->value, 'api');
        $sales->syncPermissions($this->resourcePermissions([
            'CUSTOMERS', 'RENTAL_REQUESTS', 'QUOTATIONS', 'CONTRACTS',
        ]));

        $dispatcher = RoleModel::findOrCreate(RoleEnum::DISPATCHER->value, 'api');
        $dispatcher->syncPermissions($this->resourcePermissions([
            'VEHICLES', 'DRIVERS', 'ROUTES', 'ROUTE_RATES', 'TRIP_SCHEDULES',
            'TRIP_ASSIGNMENTS', 'DISPATCH_ORDERS',
        ]));

        $accountant = RoleModel::findOrCreate(RoleEnum::ACCOUNTANT->value, 'api');
        $accountant->syncPermissions(array_merge(
            $this->resourcePermissions([
                'RECEIPTS', 'EXPENSES', 'PARTNER_PAYMENTS', 'DRIVER_ATTENDANCES',
                'DRIVER_ADVANCES', 'PAYROLLS',
            ]),
            $this->permissionValues([
                PermissionEnum::CUSTOMERS_VIEW,
                PermissionEnum::PARTNERS_VIEW,
                PermissionEnum::CONTRACTS_VIEW,
            ]),
        ));

        $driver = RoleModel::findOrCreate(RoleEnum::DRIVER->value, 'api');
        $driver->syncPermissions([]);

        $this->seedRoleAccounts();
    }

    /** @return list<string> */
    private function viewPermissions(): array
    {
        return array_values(array_filter(
            PermissionEnum::values(),
            static fn (string $permission): bool => str_ends_with($permission, '.view'),
        ));
    }

    /**
     * @param  list<PermissionEnum>  $permissions
     * @return list<string>
     */
    private function permissionValues(array $permissions): array
    {
        return array_map(static fn (PermissionEnum $permission): string => $permission->value, $permissions);
    }

    /**
     * @param  list<string>  $resources
     * @return list<string>
     */
    private function resourcePermissions(array $resources): array
    {
        $permissions = [];

        foreach ($resources as $resource) {
            $permissions = array_merge($permissions, $this->permissionValues([
                constant(PermissionEnum::class.'::'.$resource.'_VIEW'),
                constant(PermissionEnum::class.'::'.$resource.'_MANAGE'),
            ]));
        }

        return $permissions;
    }

    private function seedRoleAccounts(): void
    {
        foreach ($this->roleAccounts() as $account) {
            $user = User::firstOrCreate(
                ['user_name' => $account['user_name']],
                [
                    'email' => $account['email'],
                    'password' => config('auth.seed_password'),
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles([$account['role']->value]);
        }
    }

    /**
     * @return list<array{user_name: string, email: string, role: RoleEnum}>
     */
    private function roleAccounts(): array
    {
        return [
            ['user_name' => 'admin', 'email' => 'admin@halinhtravel.test', 'role' => RoleEnum::ADMIN],
            ['user_name' => 'director', 'email' => 'director@halinhtravel.test', 'role' => RoleEnum::DIRECTOR],
            ['user_name' => 'sales', 'email' => 'sales@halinhtravel.test', 'role' => RoleEnum::SALES],
            ['user_name' => 'dispatcher', 'email' => 'dispatcher@halinhtravel.test', 'role' => RoleEnum::DISPATCHER],
            ['user_name' => 'accountant', 'email' => 'accountant@halinhtravel.test', 'role' => RoleEnum::ACCOUNTANT],
            ['user_name' => 'driver', 'email' => 'driver@halinhtravel.test', 'role' => RoleEnum::DRIVER],
        ];
    }
}
