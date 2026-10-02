<?php

namespace Tests\Unit\Finance;

use App\Modules\Contract\Models\Contract;
use App\Modules\Dispatch\Models\DispatchOrder;
use App\Modules\Finance\Models\Expense;
use App\Modules\Finance\Models\PartnerPayment;
use App\Modules\Finance\Models\Receipt;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\ExpenseType;
use App\Modules\MasterData\Models\Partner;
use App\Modules\MasterData\Models\Vehicle;
use App\Shared\Enums\ExpenseScopeEnum;
use App\Shared\Enums\PaymentMethodEnum;
use App\Shared\Enums\ReceiptTypeEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class ModelsTest extends TestCase
{
    public function test_finance_models_apply_expected_casts(): void
    {
        $receipt = new Receipt([
            'receipt_type' => 'deposit',
            'received_at' => '2026-09-25 10:30:00',
            'amount' => '1250',
            'payment_method' => 'cash',
            'is_locked' => true,
        ]);
        $expense = new Expense([
            'scope' => 'trip',
            'expense_date' => '2026-09-25 11:30:00',
            'amount' => '2500',
            'is_locked' => true,
        ]);
        $partnerPayment = new PartnerPayment([
            'paid_at' => '2026-09-25 12:30:00',
            'amount' => '3750',
            'payment_method' => 'bank_transfer',
            'is_locked' => true,
        ]);

        $this->assertSame(ReceiptTypeEnum::DEPOSIT, $receipt->receipt_type);
        $this->assertInstanceOf(Carbon::class, $receipt->received_at);
        $this->assertSame('1250.00', $receipt->amount);
        $this->assertSame(PaymentMethodEnum::CASH, $receipt->payment_method);
        $this->assertTrue($receipt->is_locked);

        $this->assertSame(ExpenseScopeEnum::TRIP, $expense->scope);
        $this->assertInstanceOf(Carbon::class, $expense->expense_date);
        $this->assertSame('2500.00', $expense->amount);
        $this->assertTrue($expense->is_locked);

        $this->assertInstanceOf(Carbon::class, $partnerPayment->paid_at);
        $this->assertSame('3750.00', $partnerPayment->amount);
        $this->assertSame(PaymentMethodEnum::BANK_TRANSFER, $partnerPayment->payment_method);
        $this->assertTrue($partnerPayment->is_locked);
    }

    public function test_finance_models_expose_relationships(): void
    {
        $this->assertInstanceOf(BelongsTo::class, (new Receipt)->customer());
        $this->assertInstanceOf(BelongsTo::class, (new Receipt)->contract());
        $this->assertInstanceOf(BelongsTo::class, (new Expense)->expenseType());
        $this->assertInstanceOf(BelongsTo::class, (new Expense)->vehicle());
        $this->assertInstanceOf(BelongsTo::class, (new Expense)->dispatchOrder());
        $this->assertInstanceOf(BelongsTo::class, (new Expense)->partner());
        $this->assertInstanceOf(BelongsTo::class, (new Expense)->driver());
        $this->assertInstanceOf(BelongsTo::class, (new PartnerPayment)->partner());
        $this->assertInstanceOf(BelongsTo::class, (new PartnerPayment)->dispatchOrder());

        $this->assertInstanceOf(HasMany::class, (new Customer)->receipts());
        $this->assertInstanceOf(HasMany::class, (new Contract)->receipts());
        $this->assertInstanceOf(HasMany::class, (new ExpenseType)->expenses());
        $this->assertInstanceOf(HasMany::class, (new Vehicle)->expenses());
        $this->assertInstanceOf(HasMany::class, (new DispatchOrder)->expenses());
        $this->assertInstanceOf(HasMany::class, (new DispatchOrder)->partnerPayments());
        $this->assertInstanceOf(HasMany::class, (new Partner)->expenses());
        $this->assertInstanceOf(HasMany::class, (new Partner)->partnerPayments());
        $this->assertInstanceOf(HasMany::class, (new Driver)->expenses());
    }
}
