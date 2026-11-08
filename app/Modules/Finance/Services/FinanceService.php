<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Interfaces\FinanceServiceInterface;
use App\Modules\Finance\Models\Expense;
use App\Modules\Finance\Models\PartnerPayment;
use App\Modules\Finance\Models\Receipt;
use App\Modules\MasterData\Models\ExpenseType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FinanceService implements FinanceServiceInterface
{
    public function receipts(): array
    {
        return Receipt::query()->with('customer:id,name')->where('is_active', true)->latest('received_at')->get()->map(fn (Receipt $item) => $this->receiptData($item))->all();
    }

    public function receipt(Receipt $receipt): array
    {
        return $this->receiptData($receipt->load('customer:id,name'));
    }

    public function createReceipt(array $data): array
    {
        return DB::transaction(fn (): array => $this->receipt(Receipt::query()->create([...$data, 'receipt_no' => $this->nextNo(Receipt::class, 'PT')])));
    }

    public function updateReceipt(Receipt $receipt, array $data): array
    {
        $this->assertUnlocked($receipt);
        $receipt->fill($data)->save();

        return $this->receipt($receipt->fresh());
    }

    public function deactivateReceipt(Receipt $receipt): void
    {
        $this->assertUnlocked($receipt);
        $receipt->forceFill(['is_active' => false])->save();
    }

    public function lockReceipt(Receipt $receipt): array
    {
        $receipt->forceFill(['is_locked' => true])->save();

        return $this->receipt($receipt->fresh());
    }

    public function expenses(array $filters = []): array
    {
        $query = Expense::query()->with(['expenseType:id,name,code,scope,profit_category', 'vehicle:id,license_plate'])->where('is_active', true);
        foreach (['vehicle_id', 'expense_type_id'] as $key) if (filled($filters[$key] ?? null)) $query->where($key, $filters[$key]);
        if (filled($filters['scope'] ?? null)) $query->whereHas('expenseType', fn ($q) => $q->where('scope', $filters['scope']));
        if (filled($filters['from_date'] ?? null)) $query->whereDate('expense_date', '>=', $filters['from_date']);
        if (filled($filters['to_date'] ?? null)) $query->whereDate('expense_date', '<=', $filters['to_date']);
        return $query->latest('expense_date')->get()->map(fn (Expense $item) => $this->expenseData($item))->all();
    }

    public function expense(Expense $expense): array
    {
        return $this->expenseData($expense->load(['expenseType:id,name,code,scope,profit_category', 'vehicle:id,license_plate']));
    }

    public function createExpense(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $this->assertExpenseScope($data);
            $this->assertHiredDriverName($data);

            return $this->expense(Expense::query()->create([...$data, 'expense_no' => $this->nextNo(Expense::class, 'PC')]));
        });
    }

    public function updateExpense(Expense $expense, array $data): array
    {
        $this->assertUnlocked($expense);
        $data = [...$expense->getAttributes(), ...$data];
        $this->assertExpenseScope($data);
        $this->assertHiredDriverName($data);
        $expense->fill($data)->save();

        return $this->expense($expense->fresh());
    }

    public function deactivateExpense(Expense $expense): void
    {
        $this->assertUnlocked($expense);
        $expense->forceFill(['is_active' => false])->save();
    }

    public function lockExpense(Expense $expense): array
    {
        $expense->forceFill(['is_locked' => true])->save();

        return $this->expense($expense->fresh());
    }

    public function partnerPayments(): array
    {
        return PartnerPayment::query()->with('partner:id,name')->where('is_active', true)->latest('paid_at')->get()->map(fn (PartnerPayment $item) => $this->partnerPaymentData($item))->all();
    }

    public function partnerPayment(PartnerPayment $partnerPayment): array
    {
        return $this->partnerPaymentData($partnerPayment->load('partner:id,name'));
    }

    public function createPartnerPayment(array $data): array
    {
        return DB::transaction(fn (): array => $this->partnerPayment(PartnerPayment::query()->create([...$data, 'payment_no' => $this->nextNo(PartnerPayment::class, 'CTDT')])));
    }

    public function updatePartnerPayment(PartnerPayment $partnerPayment, array $data): array
    {
        $this->assertUnlocked($partnerPayment);
        $partnerPayment->fill($data)->save();

        return $this->partnerPayment($partnerPayment->fresh());
    }

    public function deactivatePartnerPayment(PartnerPayment $partnerPayment): void
    {
        $this->assertUnlocked($partnerPayment);
        $partnerPayment->forceFill(['is_active' => false])->save();
    }

    public function lockPartnerPayment(PartnerPayment $partnerPayment): array
    {
        $partnerPayment->forceFill(['is_locked' => true])->save();

        return $this->partnerPayment($partnerPayment->fresh());
    }

    private function assertUnlocked(Receipt|Expense|PartnerPayment $document): void
    {
        if ($document->is_locked) {
            abort(409, 'Chứng từ đã khóa sổ và không thể chỉnh sửa.');
        }
    }

    private function assertHiredDriverName(array $data): void
    {
        $type = ExpenseType::query()->find($data['expense_type_id'] ?? null);
        if ($type && (Str::contains(Str::lower($type->code), 'hired_driver') || Str::contains(Str::lower($type->name), 'thuê lái')) && blank($data['hired_driver_name'] ?? null)) {
            throw ValidationException::withMessages(['hired_driver_name' => 'Chi trả thuê lái cần nhập tên lái thuê.']);
        }
    }

    private function assertExpenseScope(array $data): void
    {
        $type = ExpenseType::query()->find($data['expense_type_id'] ?? null);
        if (! $type) return;
        if ($type->scope->value === 'vehicle' && empty($data['vehicle_id'])) throw ValidationException::withMessages(['vehicle_id' => 'Chi phí xe phải chọn xe.']);
        if ($type->scope->value === 'general' && ! empty($data['vehicle_id'])) throw ValidationException::withMessages(['vehicle_id' => 'Chi phí chung không được gắn xe.']);
    }

    private function nextNo(string $model, string $prefix): string
    {
        return $prefix.now()->format('Ymd').str_pad((string) ($model::query()->where('created_at', '>=', now()->startOfDay())->count() + 1), 3, '0', STR_PAD_LEFT);
    }

    private function receiptData(Receipt $item): array
    {
        return [...$item->only(['id', 'receipt_no', 'customer_id', 'receipt_type', 'received_at', 'amount', 'payment_method', 'payer_name', 'description', 'is_locked', 'is_active']), 'receipt_type' => $item->receipt_type?->value, 'payment_method' => $item->payment_method?->value, 'customer_name' => $item->customer?->name];
    }

    private function expenseData(Expense $item): array
    {
        return [...$item->only(['id', 'expense_no', 'expense_type_id', 'vehicle_id', 'expense_date', 'amount', 'payment_method', 'recipient_name', 'paid_by', 'hired_driver_name', 'description', 'is_locked', 'is_active']), 'expense_type_name' => $item->expenseType?->name, 'expense_scope' => $item->expenseType?->scope?->value, 'vehicle_plate' => $item->vehicle?->license_plate];
    }

    private function partnerPaymentData(PartnerPayment $item): array
    {
        return [...$item->only(['id', 'payment_no', 'partner_id', 'paid_at', 'amount', 'payment_method', 'recipient_name', 'description', 'is_locked', 'is_active']), 'payment_method' => $item->payment_method?->value, 'partner_name' => $item->partner?->name];
    }
}
