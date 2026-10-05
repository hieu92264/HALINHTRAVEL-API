<?php

namespace Tests\Feature;

use App\Modules\Auth\Models\User;
use App\Modules\DriverPayroll\Models\DriverAdvance;
use App\Shared\Enums\DriverAdvanceStatusEnum;
use Database\Seeders\ImportedHalinhTravelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ImportedHalinhTravelSeederTest extends TestCase
{
    use RefreshDatabase;

    private string $snapshotPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->snapshotPath = tempnam(sys_get_temp_dir(), 'halinh-travel-') ?: '';
        file_put_contents($this->snapshotPath, $this->minimalSnapshot());
        config(['halinh_travel_import.sql_path' => $this->snapshotPath]);
    }

    protected function tearDown(): void
    {
        if ($this->snapshotPath !== '' && file_exists($this->snapshotPath)) {
            unlink($this->snapshotPath);
        }

        parent::tearDown();
    }

    public function test_imported_users_use_supported_roles_and_a_hashed_password(): void
    {
        $this->seed(ImportedHalinhTravelSeeder::class);

        $admin = User::query()->where('user_name', 'admin')->firstOrFail();
        $driver = User::query()->where('user_name', 'nguyenha')->firstOrFail();

        $this->assertSame('admin@halinhtravel.com', $admin->email);
        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($driver->hasRole('driver'));
        $this->assertSame(DriverAdvanceStatusEnum::class, (new DriverAdvance)->getCasts()['status']);
    }

    public function test_import_is_idempotent_for_existing_users(): void
    {
        $this->seed(ImportedHalinhTravelSeeder::class);
        $this->seed(ImportedHalinhTravelSeeder::class);

        $this->assertSame(7, User::query()->count());
        $this->assertSame(1, User::query()->where('user_name', 'nguyenha')->count());
    }

    public function test_full_local_snapshot_has_the_expected_normalized_relationships(): void
    {
        $path = getenv('HALINH_TRAVEL_FULL_SNAPSHOT_TEST_PATH');

        if (! is_string($path) || ! file_exists($path)) {
            $this->markTestSkipped('HALINH_TRAVEL_FULL_SNAPSHOT_TEST_PATH is not configured.');
        }

        config(['halinh_travel_import.sql_path' => $path]);

        $this->seed(ImportedHalinhTravelSeeder::class);

        $this->assertDatabaseCount('customers', 12);
        $this->assertDatabaseCount('quotations', 12);
        $this->assertDatabaseCount('contracts', 5);
        $this->assertDatabaseCount('dispatch_orders', 5);
        $this->assertDatabaseCount('payroll_items', 45);
        $this->assertDatabaseCount('payroll_item_details', 5);
        $this->assertSame(12, \DB::table('quotations')->whereNotNull('rental_request_id')->count());
        $this->assertSame(5, \DB::table('rental_requests')->where('status', 'converted')->count());
        $this->assertSame(5, \DB::table('driver_advances')->where('status', 'approved')->count());

        foreach (['nguyenha', 'cuongtran', 'tuhoang', 'tuanvu'] as $userName) {
            $this->assertTrue(User::query()->where('user_name', $userName)->firstOrFail()->hasRole('driver'));
        }

        $this->seed(ImportedHalinhTravelSeeder::class);

        $this->assertDatabaseCount('payroll_items', 45);
    }

    private function minimalSnapshot(): string
    {
        return <<<'SQL'
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL,
  `user_name_created` varchar(100) NULL,
  `user_name_updated` varchar(100) NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  `user_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `last_login_at` datetime NULL,
  `email_verified_at` timestamp NULL,
  `remember_token` varchar(100) NULL
) ENGINE=InnoDB;
INSERT INTO `users` VALUES (1, 1, NULL, NULL, '2026-10-01 08:00:00', '2026-10-01 08:00:00', 'admin', 'admin@halinhtravel.com', 'ignored', NULL, '2026-10-01 08:00:00', NULL);
INSERT INTO `users` VALUES (2, 1, NULL, NULL, '2026-10-01 08:00:00', '2026-10-01 08:00:00', 'nguyenha', 'hanguyen@example.test', 'ignored', NULL, '2026-10-01 08:00:00', NULL);
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint unsigned NOT NULL
) ENGINE=InnoDB;
INSERT INTO `model_has_roles` VALUES (1, 'App\\Modules\\Auth\\Models\\User', 1);
INSERT INTO `model_has_roles` VALUES (7, 'App\\Modules\\Auth\\Models\\User', 2);
SQL;
    }
}
