<?php

namespace Tests\Feature\Rental;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\MasterData\Models\Customer;
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

    public function test_rejection_from_email_closes_the_rental_request_and_invalid_tokens_are_not_exposed(): void
    {
        [$customer, $vehicleType, $request] = $this->references();
        $quotation = $this->createQuotation($customer, $vehicleType, $request, QuotationStatusEnum::SENT);
        $token = 'valid-token';
        $quotation->responseTokens()->create(['token_hash' => hash('sha256', $token), 'expires_at' => now()->addDay()]);
        $request->forceFill(['status' => RentalRequestStatusEnum::QUOTED])->save();

        $this->postJson("/api/public/quotation-responses/{$token}/reject", ['note' => 'Chưa phù hợp'])
            ->assertOk()->assertJsonPath('metadata.status', 'rejected');
        $this->assertDatabaseHas('rental_requests', ['id' => $request->id, 'status' => 'rejected']);
        $this->getJson('/api/public/quotation-responses/not-a-token')->assertGone();
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
        $request = RentalRequest::create(['request_no' => 'YC20260001', 'customer_id' => $customer->id, 'requested_at' => '2026-10-05 09:30:00', 'service_type' => 'tourism', 'pickup_location' => 'Hà Nội', 'dropoff_location' => 'Hạ Long', 'start_at' => '2026-10-20 06:00:00', 'status' => 'new']);

        return [$customer, $vehicleType, $request];
    }

    private function createQuotation(Customer $customer, VehicleType $vehicleType, RentalRequest $request, QuotationStatusEnum $status = QuotationStatusEnum::DRAFT): Quotation
    {
        $quotation = Quotation::create(['quotation_no' => 'BG20260001', 'customer_id' => $customer->id, 'rental_request_id' => $request->id, 'quotation_date' => '2026-10-05', 'subtotal' => '1200000', 'discount_amount' => '0', 'total_amount' => '1200000', 'status' => $status]);
        $quotation->items()->create(['vehicle_type_id' => $vehicleType->id, 'quantity' => 1, 'unit_price' => '1200000', 'amount' => '1200000']);

        return $quotation;
    }

    private function payload(Customer $customer, VehicleType $vehicleType, RentalRequest $request): array
    {
        return ['customer_id' => $customer->id, 'rental_request_id' => $request->id, 'quotation_date' => '2026-10-05', 'valid_until' => '2026-10-10', 'discount_amount' => '100000', 'items' => [['vehicle_type_id' => $vehicleType->id, 'quantity' => 2, 'unit_price' => '1200000']]];
    }

    private function sales(): User
    {
        $this->seed(AuthDatabaseSeeder::class);

        return User::query()->where('user_name', 'sales')->firstOrFail();
    }
}
