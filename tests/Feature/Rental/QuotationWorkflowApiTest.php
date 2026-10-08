<?php

namespace Tests\Feature\Rental;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\Vehicle;
use App\Modules\MasterData\Models\VehicleType;
use App\Modules\Rental\Mail\QuotationSentMail;
use App\Modules\Rental\Models\Quotation;
use App\Modules\Rental\Models\RentalRequest;
use App\Shared\Enums\QuotationStatusEnum;
use App\Shared\Enums\RentalRequestStatusEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class QuotationWorkflowApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('rental.frontend_quotation_response_url', 'https://frontend.test/quotation-response');
    }

    public function test_sales_can_create_a_draft_quotation_with_server_calculated_totals(): void
    {
        [$customer, $vehicleType, $request] = $this->references();
        $this->actingAs($this->sales(), 'api')->postJson('/api/rental/quotations', $this->payload($customer, $vehicleType, $request))
            ->assertCreated()
            ->assertJsonPath('metadata.quotation_no', 'BG20260001')
            ->assertJsonPath('metadata.status', 'draft')
            ->assertJsonPath('metadata.subtotal', '2400000.00')
            ->assertJsonPath('metadata.total_amount', '2300000.00');

        $this->assertDatabaseHas('quotation_items', ['quantity' => 2, 'unit_price' => 1200000, 'amount' => 2400000]);
    }

    public function test_creating_a_quotation_from_a_request_requires_available_vehicles_and_drivers(): void
    {
        [$customer, $vehicleType, $request] = $this->references();
        Vehicle::query()->update(['vehicle_status' => 'maintenance']);

        $this->actingAs($this->sales(), 'api')
            ->postJson('/api/rental/quotations', $this->payload($customer, $vehicleType, $request))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Không đủ năng lực để lập báo giá: Xe 16 chỗ thiếu 2 xe.');

        Vehicle::query()->update(['vehicle_status' => 'available']);
        Driver::query()->update(['left_at' => '2026-10-20']);

        $this->actingAs($this->sales(), 'api')
            ->postJson('/api/rental/quotations', $this->payload($customer, $vehicleType, $request))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Không đủ năng lực để lập báo giá: thiếu 2 tài xế.');
    }

    public function test_creating_a_quotation_from_a_request_requires_a_planned_end_and_an_eligible_status(): void
    {
        [$customer, $vehicleType, $request] = $this->references();
        $request->forceFill(['end_at' => null])->save();

        $this->actingAs($this->sales(), 'api')
            ->postJson('/api/rental/quotations', $this->payload($customer, $vehicleType, $request))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Yêu cầu thuê xe cần có thời gian khởi hành và dự kiến kết thúc trước khi lập báo giá.');

        $request->forceFill([
            'end_at' => '2026-10-20 18:00:00',
            'status' => RentalRequestStatusEnum::ACCEPTED,
        ])->save();

        $this->actingAs($this->sales(), 'api')
            ->postJson('/api/rental/quotations', $this->payload($customer, $vehicleType, $request))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Yêu cầu thuê xe không hợp lệ để lập báo giá.');
    }

    public function test_duplicate_vehicle_types_on_a_request_are_aggregated_for_capacity_checking(): void
    {
        [$customer, $vehicleType, $request] = $this->references();
        $request->items()->create(['vehicle_type_id' => $vehicleType->id, 'quantity' => 1]);
        $this->driver('LX0003');

        $payload = $this->payload($customer, $vehicleType, $request);
        $payload['items'][0]['quantity'] = 3;
        $this->actingAs($this->sales(), 'api')
            ->postJson('/api/rental/quotations', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Không đủ năng lực để lập báo giá: Xe 16 chỗ thiếu 1 xe.');
    }

    public function test_quotation_scope_must_match_the_rental_request(): void
    {
        [$customer, $vehicleType, $request] = $this->references();
        $payload = $this->payload($customer, $vehicleType, $request);
        $payload['items'][0]['quantity'] = 1;

        $this->actingAs($this->sales(), 'api')
            ->postJson('/api/rental/quotations', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Hạng mục báo giá phải khớp loại xe, tuyến và số lượng của yêu cầu thuê xe.');
    }

    public function test_quotation_must_be_linked_to_a_rental_request(): void
    {
        [$customer, $vehicleType, $request] = $this->references();
        Vehicle::query()->update(['vehicle_status' => 'maintenance']);
        Driver::query()->update(['left_at' => '2026-10-20']);
        $payload = $this->payload($customer, $vehicleType, $request);
        $payload['rental_request_id'] = null;

        $this->actingAs($this->sales(), 'api')
            ->postJson('/api/rental/quotations', $payload)
            ->assertUnprocessable();
    }

    public function test_sending_then_accepting_from_email_updates_the_quotation_and_request(): void
    {
        [$customer, $vehicleType, $request] = $this->references();
        $quotation = $this->createQuotation($customer, $vehicleType, $request);
        Mail::fake();

        $this->actingAs($this->sales(), 'api')->postJson("/api/rental/quotations/{$quotation->id}/send")
            ->assertOk()->assertJsonPath('metadata.status', 'sent');
        $this->assertDatabaseHas('rental_requests', ['id' => $request->id, 'status' => 'quoted']);
        $this->assertDatabaseCount('quotation_response_tokens', 1);

        $mail = null;
        Mail::assertQueued(QuotationSentMail::class, function (QuotationSentMail $queued) use (&$mail): bool {
            $mail = $queued;

            return true;
        });
        parse_str((string) parse_url($mail->responseUrl, PHP_URL_QUERY), $query);
        $token = $query['token'];

        $this->postJson("/api/public/quotation-responses/{$token}/accept")
            ->assertOk()->assertJsonPath('metadata.status', 'approved');
        $this->assertDatabaseHas('quotations', ['id' => $quotation->id, 'status' => 'approved']);
        $this->assertDatabaseHas('rental_requests', ['id' => $request->id, 'status' => 'accepted']);
        $this->postJson("/api/public/quotation-responses/{$token}/accept")->assertGone();
    }

    public function test_rejection_from_email_keeps_the_rental_request_open_and_invalid_tokens_are_not_exposed(): void
    {
        [$customer, $vehicleType, $request] = $this->references();
        $quotation = $this->createQuotation($customer, $vehicleType, $request, QuotationStatusEnum::SENT);
        $token = 'valid-token';
        $quotation->responseTokens()->create(['token_hash' => hash('sha256', $token), 'expires_at' => now()->addDay()]);
        $request->forceFill(['status' => RentalRequestStatusEnum::QUOTED])->save();

        $this->postJson("/api/public/quotation-responses/{$token}/reject", ['note' => 'Chưa phù hợp'])
            ->assertOk()->assertJsonPath('metadata.status', 'rejected');
        $this->assertDatabaseHas('rental_requests', ['id' => $request->id, 'status' => 'quoted']);
        $this->getJson('/api/public/quotation-responses/not-a-token')->assertGone();
    }

    public function test_sales_can_record_a_phone_acceptance_for_a_draft_when_customer_has_no_email(): void
    {
        [$customer, $vehicleType, $request] = $this->references();
        $customer->forceFill(['email' => null])->save();
        $quotation = $this->createQuotation($customer, $vehicleType, $request);

        $this->actingAs($this->sales(), 'api')
            ->postJson("/api/rental/quotations/{$quotation->id}/record-customer-response", [
                'accepted' => true,
                'note' => 'Khách xác nhận qua điện thoại.',
            ])
            ->assertOk()
            ->assertJsonPath('metadata.status', 'approved')
            ->assertJsonPath('metadata.approved_by', 'sales')
            ->assertJsonPath('metadata.customer_response_note', 'Khách xác nhận qua điện thoại.');

        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'status' => 'approved',
            'approved_by' => 'sales',
        ]);
        $this->assertDatabaseHas('rental_requests', ['id' => $request->id, 'status' => 'accepted']);
    }

    public function test_phone_response_can_reject_a_sent_quote_and_invalidates_its_email_token(): void
    {
        [$customer, $vehicleType, $request] = $this->references();
        $quotation = $this->createQuotation($customer, $vehicleType, $request, QuotationStatusEnum::SENT);
        $quotation->responseTokens()->create([
            'token_hash' => hash('sha256', 'email-token'),
            'expires_at' => now()->addDay(),
        ]);
        $request->forceFill(['status' => RentalRequestStatusEnum::QUOTED])->save();

        $this->actingAs($this->sales(), 'api')
            ->postJson("/api/rental/quotations/{$quotation->id}/record-customer-response", [
                'accepted' => false,
                'note' => 'Khách từ chối qua điện thoại.',
            ])
            ->assertOk()
            ->assertJsonPath('metadata.status', 'rejected');

        $this->assertDatabaseHas('rental_requests', ['id' => $request->id, 'status' => 'quoted']);
        $this->postJson('/api/public/quotation-responses/email-token/accept')->assertGone();
    }

    public function test_accepting_one_quote_supersedes_other_open_quotes_and_their_tokens(): void
    {
        [$customer, $vehicleType, $request] = $this->references();
        $winner = $this->createQuotation($customer, $vehicleType, $request, QuotationStatusEnum::SENT);
        $alternative = Quotation::create([
            'quotation_no' => 'BG20260002',
            'customer_id' => $customer->id,
            'rental_request_id' => $request->id,
            'quotation_date' => '2026-10-05',
            'valid_until' => '2030-10-10',
            'subtotal' => '1200000',
            'discount_amount' => '0',
            'total_amount' => '1200000',
            'status' => QuotationStatusEnum::SENT,
        ]);
        $alternative->items()->create(['vehicle_type_id' => $vehicleType->id, 'quantity' => 1, 'unit_price' => '1200000', 'amount' => '1200000']);
        $alternative->responseTokens()->create(['token_hash' => hash('sha256', 'alternative-token'), 'expires_at' => now()->addDay()]);
        $request->forceFill(['status' => RentalRequestStatusEnum::QUOTED])->save();

        $this->actingAs($this->sales(), 'api')
            ->postJson("/api/rental/quotations/{$winner->id}/record-customer-response", ['accepted' => true])
            ->assertOk();

        $this->assertDatabaseHas('quotations', ['id' => $alternative->id, 'status' => 'superseded']);
        $this->postJson('/api/public/quotation-responses/alternative-token/accept')->assertGone();
    }

    public function test_phone_response_is_rejected_after_validity_date(): void
    {
        [$customer, $vehicleType, $request] = $this->references();
        $customer->forceFill(['email' => null])->save();
        $quotation = $this->createQuotation($customer, $vehicleType, $request);
        $quotation->forceFill(['valid_until' => now()->subDay()->toDateString()])->save();

        $this->actingAs($this->sales(), 'api')
            ->postJson("/api/rental/quotations/{$quotation->id}/record-customer-response", ['accepted' => true])
            ->assertUnprocessable();
    }

    public function test_expiration_command_expires_open_quotations_past_their_validity_date(): void
    {
        [$customer, $vehicleType, $request] = $this->references();
        $quotation = $this->createQuotation($customer, $vehicleType, $request);
        $quotation->forceFill(['valid_until' => now()->subDay()->toDateString()])->save();

        $this->artisan('rental:expire-quotations')->assertExitCode(0);

        $this->assertDatabaseHas('quotations', ['id' => $quotation->id, 'status' => 'expired']);
    }

    public function test_rejecting_a_request_supersedes_all_open_quotes(): void
    {
        [$customer, $vehicleType, $request] = $this->references();
        $draft = $this->createQuotation($customer, $vehicleType, $request);
        $sent = Quotation::create([
            'quotation_no' => 'BG20260002',
            'customer_id' => $customer->id,
            'rental_request_id' => $request->id,
            'quotation_date' => '2026-10-05',
            'valid_until' => '2030-10-10',
            'subtotal' => '1200000',
            'discount_amount' => '0',
            'total_amount' => '1200000',
            'status' => QuotationStatusEnum::SENT,
        ]);
        $sent->items()->create(['vehicle_type_id' => $vehicleType->id, 'quantity' => 1, 'unit_price' => '1200000', 'amount' => '1200000']);

        $this->actingAs($this->sales(), 'api')->postJson("/api/rental/requests/{$request->id}/reject")
            ->assertOk();

        $this->assertDatabaseHas('quotations', ['id' => $draft->id, 'status' => 'superseded']);
        $this->assertDatabaseHas('quotations', ['id' => $sent->id, 'status' => 'superseded']);
    }

    public function test_phone_response_requires_a_sent_quote_or_a_draft_without_customer_email(): void
    {
        [$customer, $vehicleType, $request] = $this->references();
        $quotation = $this->createQuotation($customer, $vehicleType, $request);

        $this->actingAs($this->sales(), 'api')
            ->postJson("/api/rental/quotations/{$quotation->id}/record-customer-response", ['accepted' => true])
            ->assertConflict();

        $this->actingAs($this->seededUser('driver'), 'api')
            ->postJson("/api/rental/quotations/{$quotation->id}/record-customer-response", ['accepted' => true])
            ->assertForbidden();
    }

    public function test_only_drafts_can_be_updated_or_deleted_and_expired_quote_keeps_request_quoted(): void
    {
        [$customer, $vehicleType, $request] = $this->references();
        $quotation = $this->createQuotation($customer, $vehicleType, $request, QuotationStatusEnum::SENT);
        $request->forceFill(['status' => RentalRequestStatusEnum::QUOTED])->save();

        $this->actingAs($this->sales(), 'api')->patchJson("/api/rental/quotations/{$quotation->id}", ['payment_terms' => 'x'])->assertConflict();
        $this->actingAs($this->sales(), 'api')->deleteJson("/api/rental/quotations/{$quotation->id}")->assertConflict();
        $this->actingAs($this->sales(), 'api')->postJson("/api/rental/quotations/{$quotation->id}/expire")
            ->assertOk()->assertJsonPath('metadata.status', 'expired');
        $this->assertDatabaseHas('rental_requests', ['id' => $request->id, 'status' => 'quoted']);
    }

    /** @return array{Customer, VehicleType, RentalRequest} */
    private function references(): array
    {
        $customer = Customer::create(['code' => 'KH0001', 'type' => 'individual', 'name' => 'Khách thuê xe', 'email' => 'customer@example.test', 'cccd' => '001234567890']);
        $vehicleType = VehicleType::create(['code' => 'XE16', 'name' => 'Xe 16 chỗ', 'seats' => 16]);
        $request = RentalRequest::create(['request_no' => 'YC20260001', 'customer_id' => $customer->id, 'requested_at' => '2026-10-05 09:30:00', 'service_type' => 'tourism', 'pickup_location' => 'Hà Nội', 'dropoff_location' => 'Hạ Long', 'start_at' => '2026-10-20 06:00:00', 'end_at' => '2026-10-20 18:00:00', 'status' => 'new']);
        $request->items()->create(['vehicle_type_id' => $vehicleType->id, 'quantity' => 2]);
        $this->vehicle($vehicleType, '15B-000.01');
        $this->vehicle($vehicleType, '15B-000.02');
        $this->driver('LX0001');
        $this->driver('LX0002');

        return [$customer, $vehicleType, $request];
    }

    private function createQuotation(Customer $customer, VehicleType $vehicleType, RentalRequest $request, QuotationStatusEnum $status = QuotationStatusEnum::DRAFT): Quotation
    {
        $quotation = Quotation::create(['quotation_no' => 'BG20260001', 'customer_id' => $customer->id, 'rental_request_id' => $request->id, 'quotation_date' => '2026-10-05', 'valid_until' => '2030-10-10', 'subtotal' => '1200000', 'discount_amount' => '0', 'total_amount' => '1200000', 'status' => $status]);
        $quotation->items()->create(['vehicle_type_id' => $vehicleType->id, 'quantity' => 1, 'unit_price' => '1200000', 'amount' => '1200000']);

        return $quotation;
    }

    private function payload(Customer $customer, VehicleType $vehicleType, RentalRequest $request): array
    {
        return ['customer_id' => $customer->id, 'rental_request_id' => $request->id, 'quotation_date' => '2026-10-05', 'valid_until' => '2026-10-10', 'discount_amount' => '100000', 'items' => [['vehicle_type_id' => $vehicleType->id, 'quantity' => 2, 'unit_price' => '1200000']]];
    }

    private function vehicle(VehicleType $type, string $licensePlate): void
    {
        Vehicle::create([
            'license_plate' => $licensePlate,
            'vehicle_type_id' => $type->id,
            'ownership_type' => 'company',
            'vehicle_status' => 'available',
            'is_active' => true,
        ]);
    }

    private function driver(string $code): void
    {
        Driver::create([
            'code' => $code,
            'type' => 'company',
            'full_name' => "Tài xế {$code}",
            'license_number' => "GPLX-{$code}",
            'license_class' => 'D',
            'license_expired_at' => '2030-01-01',
            'joined_at' => '2020-01-01',
            'is_active' => true,
        ]);
    }

    private function sales(): User
    {
        $this->seed(AuthDatabaseSeeder::class);

        return User::query()->where('user_name', 'sales')->firstOrFail();
    }

    private function seededUser(string $userName): User
    {
        $this->seed(AuthDatabaseSeeder::class);

        return User::query()->where('user_name', $userName)->firstOrFail();
    }
}
