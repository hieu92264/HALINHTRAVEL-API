<?php

namespace Tests\Unit\Rental;

use App\Modules\Rental\Models\Quotation;
use App\Modules\Rental\Models\QuotationItem;
use App\Modules\Rental\Models\RentalRequest;
use App\Modules\Rental\Models\RentalRequestItem;
use App\Shared\Enums\QuotationStatusEnum;
use App\Shared\Enums\RentalRequestStatusEnum;
use App\Shared\Enums\RentalServiceTypeEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class ModelsTest extends TestCase
{
    public function test_models_apply_expected_casts(): void
    {
        $rentalRequest = new RentalRequest([
            'requested_at' => '2026-01-15 08:30:00',
            'service_type' => 'tourism',
            'start_at' => '2026-02-01 08:00:00',
            'status' => 'quoted',
        ]);
        $rentalRequestItem = new RentalRequestItem(['quantity' => '2']);
        $quotation = new Quotation([
            'quotation_date' => '2026-01-15',
            'subtotal' => '1200000',
            'discount_amount' => '50000',
            'total_amount' => '1150000',
            'status' => 'approved',
            'approved_at' => '2026-01-16 09:00:00',
        ]);
        $quotationItem = new QuotationItem([
            'quantity' => '2',
            'unit_price' => '600000',
            'amount' => '1200000',
        ]);

        $this->assertInstanceOf(Carbon::class, $rentalRequest->requested_at);
        $this->assertSame(RentalServiceTypeEnum::TOURISM, $rentalRequest->service_type);
        $this->assertInstanceOf(Carbon::class, $rentalRequest->start_at);
        $this->assertSame(RentalRequestStatusEnum::QUOTED, $rentalRequest->status);
        $this->assertSame(2, $rentalRequestItem->quantity);
        $this->assertInstanceOf(Carbon::class, $quotation->quotation_date);
        $this->assertSame('1200000.00', $quotation->subtotal);
        $this->assertSame('50000.00', $quotation->discount_amount);
        $this->assertSame('1150000.00', $quotation->total_amount);
        $this->assertSame(QuotationStatusEnum::APPROVED, $quotation->status);
        $this->assertInstanceOf(Carbon::class, $quotation->approved_at);
        $this->assertSame(2, $quotationItem->quantity);
        $this->assertSame('600000.00', $quotationItem->unit_price);
        $this->assertSame('1200000.00', $quotationItem->amount);
    }

    public function test_models_expose_relationships(): void
    {
        $this->assertInstanceOf(BelongsTo::class, (new RentalRequest)->customer());
        $this->assertInstanceOf(HasMany::class, (new RentalRequest)->items());
        $this->assertInstanceOf(HasMany::class, (new RentalRequest)->quotations());
        $this->assertInstanceOf(BelongsTo::class, (new RentalRequestItem)->rentalRequest());
        $this->assertInstanceOf(BelongsTo::class, (new RentalRequestItem)->vehicleType());
        $this->assertInstanceOf(BelongsTo::class, (new RentalRequestItem)->route());
        $this->assertInstanceOf(BelongsTo::class, (new Quotation)->rentalRequest());
        $this->assertInstanceOf(BelongsTo::class, (new Quotation)->customer());
        $this->assertInstanceOf(HasMany::class, (new Quotation)->items());
        $this->assertInstanceOf(BelongsTo::class, (new Quotation)->approvedBy());
        $this->assertInstanceOf(BelongsTo::class, (new QuotationItem)->quotation());
        $this->assertInstanceOf(BelongsTo::class, (new QuotationItem)->route());
        $this->assertInstanceOf(BelongsTo::class, (new QuotationItem)->vehicleType());
    }
}
