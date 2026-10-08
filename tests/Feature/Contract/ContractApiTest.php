<?php

namespace Tests\Feature\Contract;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\VehicleType;
use App\Modules\Rental\Models\Quotation;
use App\Modules\Rental\Models\RentalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_can_create_and_activate_a_draft_contract(): void
    {
        [$customer, $vehicleType] = $this->references();
        $response = $this->actingAs($this->sales(), 'api')->postJson('/api/contract/contracts', $this->payload($customer, $vehicleType));
        $response->assertCreated()->assertJsonPath('metadata.contract_no', 'HD20260001')->assertJsonPath('metadata.status', 'draft')->assertJsonPath('metadata.total_amount', '2400000.00');
        $id = $response->json('metadata.id');
        $this->actingAs($this->sales(), 'api')->postJson("/api/contract/contracts/{$id}/activate")->assertOk()->assertJsonPath('metadata.status', 'active');
        $this->actingAs($this->sales(), 'api')->patchJson("/api/contract/contracts/{$id}", ['terms' => 'x'])->assertConflict();
    }

    public function test_creating_from_an_approved_quotation_copies_data_and_converts_request(): void
    {
        [$customer, $vehicleType] = $this->references();
        $request = RentalRequest::create(['request_no' => 'YC20260001', 'customer_id' => $customer->id, 'requested_at' => '2026-10-01', 'service_type' => 'tourism', 'pickup_location' => 'Hà Nội', 'dropoff_location' => 'Hạ Long', 'start_at' => '2026-10-20 06:00:00', 'end_at' => '2026-10-20 18:00:00', 'status' => 'accepted']);
        $quotation = Quotation::create(['quotation_no' => 'BG20260001', 'rental_request_id' => $request->id, 'customer_id' => $customer->id, 'quotation_date' => '2026-10-01', 'subtotal' => '1200000', 'discount_amount' => '0', 'total_amount' => '1200000', 'status' => 'approved']);
        $quotation->items()->create(['vehicle_type_id' => $vehicleType->id, 'quantity' => 1, 'unit_price' => '1200000', 'amount' => '1200000', 'description' => 'Theo báo giá']);
        $payload = ['quotation_id' => $quotation->id, 'contract_type' => 'trip', 'effective_from' => '2026-10-20', 'effective_to' => '2026-10-20', 'deposit_required' => '100000'];
        $this->actingAs($this->sales(), 'api')->postJson('/api/contract/contracts/from-quotation', $payload)
            ->assertCreated()->assertJsonPath('metadata.quotation_id', $quotation->id)->assertJsonPath('metadata.items.0.driver_wage', '0.00')->assertJsonPath('metadata.items.0.pickup_location', 'Hà Nội');
        $this->assertDatabaseHas('rental_requests', ['id' => $request->id, 'status' => 'converted']);
        $this->actingAs($this->sales(), 'api')->postJson('/api/contract/contracts/from-quotation', $payload)->assertConflict();
    }

    public function test_contract_validates_deposit_and_quotation_status(): void
    {
        [$customer, $vehicleType] = $this->references();
        $invalid = $this->payload($customer, $vehicleType);
        $invalid['deposit_required'] = '2500000';
        $this->actingAs($this->sales(), 'api')->postJson('/api/contract/contracts', $invalid)->assertUnprocessable();
        $quotation = Quotation::create(['quotation_no' => 'BG20260001', 'customer_id' => $customer->id, 'quotation_date' => '2026-10-01', 'subtotal' => '0', 'discount_amount' => '0', 'total_amount' => '0', 'status' => 'draft']);
        $this->actingAs($this->sales(), 'api')->postJson('/api/contract/contracts/from-quotation', ['quotation_id' => $quotation->id, 'contract_type' => 'trip', 'effective_from' => '2026-10-20'])->assertUnprocessable();
    }

    public function test_direct_contract_creation_rejects_trip_contracts_and_source_links(): void
    {
        [$customer, $vehicleType] = $this->references();
        $payload = $this->payload($customer, $vehicleType);
        $payload['contract_type'] = 'trip';

        $this->actingAs($this->sales(), 'api')->postJson('/api/contract/contracts', $payload)
            ->assertUnprocessable();
    }

    public function test_trip_contract_effective_dates_must_cover_an_overnight_request(): void
    {
        [$customer, $vehicleType] = $this->references();
        $request = RentalRequest::create([
            'request_no' => 'YC20260003',
            'customer_id' => $customer->id,
            'requested_at' => '2026-10-01',
            'service_type' => 'tourism',
            'pickup_location' => 'Hà Nội',
            'dropoff_location' => 'Hạ Long',
            'start_at' => '2026-10-20 23:00:00',
            'end_at' => '2026-10-21 02:00:00',
            'status' => 'accepted',
        ]);
        $quotation = Quotation::create([
            'quotation_no' => 'BG20260003',
            'rental_request_id' => $request->id,
            'customer_id' => $customer->id,
            'quotation_date' => '2026-10-01',
            'valid_until' => '2026-10-10',
            'subtotal' => '1200000',
            'discount_amount' => '0',
            'total_amount' => '1200000',
            'status' => 'approved',
        ]);
        $quotation->items()->create(['vehicle_type_id' => $vehicleType->id, 'quantity' => 1, 'unit_price' => '1200000', 'amount' => '1200000']);

        $this->actingAs($this->sales(), 'api')->postJson('/api/contract/contracts/from-quotation', [
            'quotation_id' => $quotation->id,
            'contract_type' => 'trip',
            'effective_from' => '2026-10-20',
            'effective_to' => '2026-10-20',
            'deposit_required' => '0',
        ])->assertUnprocessable();
    }

    public function test_creating_from_quotation_requires_an_accepted_rental_request(): void
    {
        [$customer, $vehicleType] = $this->references();
        $request = RentalRequest::create(['request_no' => 'YC20260002', 'customer_id' => $customer->id, 'requested_at' => '2026-10-01', 'service_type' => 'tourism', 'status' => 'quoted']);
        $quotation = Quotation::create(['quotation_no' => 'BG20260002', 'rental_request_id' => $request->id, 'customer_id' => $customer->id, 'quotation_date' => '2026-10-01', 'subtotal' => '1200000', 'discount_amount' => '0', 'total_amount' => '1200000', 'status' => 'approved']);
        $quotation->items()->create(['vehicle_type_id' => $vehicleType->id, 'quantity' => 1, 'unit_price' => '1200000', 'amount' => '1200000']);

        $this->actingAs($this->sales(), 'api')->postJson('/api/contract/contracts/from-quotation', [
            'quotation_id' => $quotation->id,
            'contract_type' => 'trip',
            'effective_from' => '2026-10-20',
        ])->assertUnprocessable();
    }

    /** @return array{Customer,VehicleType} */
    private function references(): array
    {
        return [Customer::create(['code' => 'KH0001', 'type' => 'individual', 'name' => 'Khách', 'cccd' => '001234567890']), VehicleType::create(['code' => 'XE16', 'name' => 'Xe 16 chỗ', 'seats' => 16])];
    }

    private function payload(Customer $customer, VehicleType $vehicleType): array
    {
        return ['customer_id' => $customer->id, 'contract_type' => 'principle', 'effective_from' => '2026-10-20', 'effective_to' => '2026-10-20', 'deposit_required' => '100000', 'items' => [['vehicle_type_id' => $vehicleType->id, 'service_type' => 'tourism', 'quantity' => 2, 'unit_price' => '1200000', 'driver_wage' => '200000', 'pickup_location' => 'Hà Nội', 'dropoff_location' => 'Hạ Long']]];
    }

    private function sales(): User
    {
        $this->seed(AuthDatabaseSeeder::class);

        return User::query()->where('user_name', 'sales')->firstOrFail();
    }
}
