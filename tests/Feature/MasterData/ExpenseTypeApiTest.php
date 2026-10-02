<?php

namespace Tests\Feature\MasterData;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\MasterData\Models\ExpenseType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseTypeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_type_routes_require_authentication_and_permission(): void
    {
        $this->getJson('/api/master-data/expense-types')->assertUnauthorized();

        $this->seed(AuthDatabaseSeeder::class);
        $driver = User::query()->where('user_name', 'driver')->firstOrFail();

        $this->actingAs($driver, 'api')
            ->getJson('/api/master-data/expense-types')
            ->assertForbidden();
    }

    public function test_admin_can_create_expense_type_with_enum_scope(): void
    {
        $admin = $this->seededUser('admin');

        $response = $this->actingAs($admin, 'api')->postJson('/api/master-data/expense-types', [
            'code' => 'FUEL',
            'name' => 'Nhiên liệu',
            'scope' => 'vehicle',
        ])->assertCreated();

        $response->assertJsonPath('metadata.code', 'FUEL')
            ->assertJsonPath('metadata.name', 'Nhiên liệu')
            ->assertJsonPath('metadata.scope', 'vehicle')
            ->assertJsonPath('metadata.is_active', true);

        $this->assertDatabaseHas('expense_types', [
            'code' => 'FUEL',
            'name' => 'Nhiên liệu',
            'scope' => 'vehicle',
        ]);
    }

    public function test_expense_type_rejects_duplicate_code_and_invalid_input(): void
    {
        $admin = $this->seededUser('admin');
        $this->createExpenseType('FUEL');

        $this->actingAs($admin, 'api')->withHeader('Accept-Language', 'vi')->postJson('/api/master-data/expense-types', [
            'code' => 'FUEL',
            'scope' => 'invalid',
        ])->assertUnprocessable()
            ->assertJsonStructure(['metadata' => ['code', 'name', 'scope']]);
    }

    public function test_expense_type_list_and_show_include_active_and_inactive_records_without_pagination(): void
    {
        $admin = $this->seededUser('admin');
        $active = $this->createExpenseType('FUEL');
        $inactive = $this->createExpenseType('TOLL', false, 'trip');

        $this->actingAs($admin, 'api')->getJson('/api/master-data/expense-types')
            ->assertOk()
            ->assertJsonCount(2, 'metadata')
            ->assertJsonFragment(['id' => $active->id, 'is_active' => true])
            ->assertJsonFragment(['id' => $inactive->id, 'is_active' => false])
            ->assertJsonMissingPath('metadata.data');

        $this->actingAs($admin, 'api')->getJson("/api/master-data/expense-types/{$inactive->id}")
            ->assertOk()
            ->assertJsonPath('metadata.code', 'TOLL')
            ->assertJsonPath('metadata.scope', 'trip')
            ->assertJsonPath('metadata.is_active', false);
    }

    public function test_expense_type_can_be_updated_deactivated_and_reactivated(): void
    {
        $admin = $this->seededUser('admin');
        $expenseType = $this->createExpenseType('FUEL');

        $this->actingAs($admin, 'api')->putJson("/api/master-data/expense-types/{$expenseType->id}", [
            'code' => 'PARKING',
            'scope' => 'general',
        ])->assertOk()
            ->assertJsonPath('metadata.code', 'PARKING')
            ->assertJsonPath('metadata.name', 'Chi phí FUEL')
            ->assertJsonPath('metadata.scope', 'general');

        $this->actingAs($admin, 'api')->deleteJson("/api/master-data/expense-types/{$expenseType->id}")
            ->assertOk();

        $this->assertDatabaseHas('expense_types', ['id' => $expenseType->id, 'is_active' => false]);

        $this->actingAs($admin, 'api')->putJson("/api/master-data/expense-types/{$expenseType->id}", [
            'is_active' => true,
        ])->assertOk()
            ->assertJsonPath('metadata.is_active', true);
    }

    private function seededUser(string $userName): User
    {
        $this->seed(AuthDatabaseSeeder::class);

        return User::query()->where('user_name', $userName)->firstOrFail();
    }

    private function createExpenseType(string $code, bool $isActive = true, string $scope = 'vehicle'): ExpenseType
    {
        return ExpenseType::create([
            'code' => $code,
            'name' => "Chi phí {$code}",
            'scope' => $scope,
            'is_active' => $isActive,
        ]);
    }
}
