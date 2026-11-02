<?php

namespace Tests\Unit\Finance;

use App\Modules\Finance\Models\Expense;
use App\Modules\Finance\Models\PartnerPayment;
use App\Modules\Finance\Models\Receipt;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\ExpenseType;
use App\Modules\MasterData\Models\Partner;
use App\Shared\Enums\PaymentMethodEnum;
use App\Shared\Enums\ReceiptTypeEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class ModelsTest extends TestCase
{
    public function test_independent_finance_documents_apply_expected_casts(): void
    {
        $receipt = new Receipt(['receipt_type' => 'deposit', 'received_at' => '2026-09-25 10:30:00', 'amount' => '1250', 'payment_method' => 'cash', 'is_locked' => true]);
        $expense = new Expense(['expense_date' => '2026-09-25 11:30:00', 'amount' => '2500', 'is_locked' => true]);
        $partnerPayment = new PartnerPayment(['paid_at' => '2026-09-25 12:30:00', 'amount' => '3750', 'payment_method' => 'bank_transfer', 'is_locked' => true]);

        $this->assertSame(ReceiptTypeEnum::DEPOSIT, $receipt->receipt_type);
        $this->assertInstanceOf(Carbon::class, $receipt->received_at);
        $this->assertSame('1250.00', $receipt->amount);
        $this->assertSame(PaymentMethodEnum::CASH, $receipt->payment_method);
        $this->assertInstanceOf(Carbon::class, $expense->expense_date);
        $this->assertSame('2500.00', $expense->amount);
        $this->assertInstanceOf(Carbon::class, $partnerPayment->paid_at);
        $this->assertSame(PaymentMethodEnum::BANK_TRANSFER, $partnerPayment->payment_method);
    }

    public function test_finance_documents_only_link_to_current_master_data(): void
    {
        $this->assertInstanceOf(BelongsTo::class, (new Receipt)->customer());
        $this->assertInstanceOf(BelongsTo::class, (new Expense)->expenseType());
        $this->assertInstanceOf(BelongsTo::class, (new PartnerPayment)->partner());
        $this->assertInstanceOf(HasMany::class, (new Customer)->receipts());
        $this->assertInstanceOf(HasMany::class, (new ExpenseType)->expenses());
        $this->assertInstanceOf(HasMany::class, (new Partner)->partnerPayments());
    }
}
