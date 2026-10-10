<?php

namespace App\Modules\Finance\Services;

use App\Modules\Contract\Models\Contract;
use App\Modules\Dispatch\Models\DispatchOrder;
use App\Modules\Finance\Interfaces\FinanceServiceInterface;
use App\Modules\Finance\Models\Expense;
use App\Modules\Finance\Models\PartnerPayment;
use App\Modules\Finance\Models\Receipt;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\ExpenseType;
use App\Modules\MasterData\Models\Partner;
use App\Modules\MasterData\Models\Vehicle;
use App\Shared\Enums\ExpenseScopeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FinanceService implements FinanceServiceInterface
{
    public function receipts(): array
    {
        return Receipt::query()->with(['customer', 'contract'])->where('is_active', true)->orderByDesc('received_at')->get()->map(fn (Receipt $item) => $this->receiptData($item))->all();
    }

    public function receipt(Receipt $receipt): array
    {
        return $this->receiptData($receipt->load(['customer', 'contract']));
    }

    public function createReceipt(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $this->validateReceipt($data);
            $item = Receipt::create([...$data, 'receipt_no' => $this->nextCode(Receipt::class, 'receipt_no', 'PT')]);

            return $this->receipt($item);
        });
    }

    public function updateReceipt(Receipt $receipt, array $data): array
    {
        return DB::transaction(function () use ($receipt, $data): array {
            $this->assertMutable($receipt);
            $merged = [...$receipt->only(['customer_id', 'contract_id', 'receipt_type', 'received_at', 'amount', 'payment_method', 'payer_name', 'description']), ...$data];
            $this->validateReceipt($merged, $receipt->id);
            $receipt->fill($data)->save();

            return $this->receipt($receipt->fresh());
        });
    }

    public function deactivateReceipt(Receipt $receipt): void
    {
        $this->assertMutable($receipt);
        $receipt->forceFill(['is_active' => false])->save();
    }

    public function lockReceipt(Receipt $receipt): array
    {
        return $this->lock($receipt, fn (Receipt $item) => $this->receipt($item));
    }

    public function expenses(): array
    {
        return Expense::query()->with(['expenseType', 'vehicle', 'dispatchOrder', 'partner', 'driver'])->where('is_active', true)->orderByDesc('expense_date')->get()->map(fn (Expense $item) => $this->expenseData($item))->all();
    }

    public function expense(Expense $expense): array
    {
        return $this->expenseData($expense->load(['expenseType', 'vehicle', 'dispatchOrder', 'partner', 'driver']));
    }

    public function createExpense(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $this->validateExpense($data);
            $item = Expense::create([...$data, 'expense_no' => $this->nextCode(Expense::class, 'expense_no', 'PC')]);

            return $this->expense($item);
        });
    }

    public function updateExpense(Expense $expense, array $data): array
    {
        return DB::transaction(function () use ($expense, $data): array {
            $this->assertMutable($expense);
            $merged = [...$expense->only(['expense_type_id', 'scope', 'vehicle_id', 'dispatch_order_id', 'partner_id', 'driver_id', 'expense_date', 'amount', 'payment_method', 'document_no', 'description']), ...$data];
            $this->validateExpense($merged);
            $expense->fill([
                ...$data,
                'vehicle_id' => $merged['vehicle_id'],
                'dispatch_order_id' => $merged['dispatch_order_id'],
            ])->save();

            return $this->expense($expense->fresh());
        });
    }

    public function deactivateExpense(Expense $expense): void
    {
        $this->assertMutable($expense);
        $expense->forceFill(['is_active' => false])->save();
    }

    public function lockExpense(Expense $expense): array
    {
        return $this->lock($expense, fn (Expense $item) => $this->expense($item));
    }

    public function partnerPayments(): array
    {
        return PartnerPayment::query()->with(['partner', 'dispatchOrder'])->where('is_active', true)->orderByDesc('paid_at')->get()->map(fn (PartnerPayment $item) => $this->partnerPaymentData($item))->all();
    }

    public function partnerPayment(PartnerPayment $partnerPayment): array
    {
        return $this->partnerPaymentData($partnerPayment->load(['partner', 'dispatchOrder']));
    }

    public function createPartnerPayment(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $this->validatePartnerPayment($data);
            $item = PartnerPayment::create([...$data, 'payment_no' => $this->nextCode(PartnerPayment::class, 'payment_no', 'CTDT')]);

            return $this->partnerPayment($item);
        });
    }

    public function updatePartnerPayment(PartnerPayment $partnerPayment, array $data): array
    {
        return DB::transaction(function () use ($partnerPayment, $data): array {
            $this->assertMutable($partnerPayment);
            $merged = [...$partnerPayment->only(['partner_id', 'dispatch_order_id', 'paid_at', 'amount', 'payment_method', 'description']), ...$data];
            $this->validatePartnerPayment($merged);
            $partnerPayment->fill($data)->save();

            return $this->partnerPayment($partnerPayment->fresh());
        });
    }

    public function deactivatePartnerPayment(PartnerPayment $partnerPayment): void
    {
        $this->assertMutable($partnerPayment);
        $partnerPayment->forceFill(['is_active' => false])->save();
    }

    public function lockPartnerPayment(PartnerPayment $partnerPayment): array
    {
        return $this->lock($partnerPayment, fn (PartnerPayment $item) => $this->partnerPayment($item));
    }

    private function validateReceipt(array $data, ?int $ignoreId = null): void
    {
        $customer = Customer::query()->whereKey($data['customer_id'])->where('is_active', true)->firstOrFail();
        if (! empty($data['contract_id'])) {
            $contract = Contract::query()->whereKey($data['contract_id'])->where('is_active', true)->firstOrFail();
            if ($contract->customer_id !== $customer->id) {
                abort(422, 'Hợp đồng phải thuộc khách hàng của phiếu thu.');
            }
            if (in_array($data['receipt_type'], ['deposit', 'contract_payment'], true)) {
                $received = (float) Receipt::query()->where('is_active', true)->where('contract_id', $contract->id)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->sum('amount');
                if ($received + (float) $data['amount'] > (float) $contract->total_amount) {
                    abort(422, 'Tổng thu không được vượt giá trị hợp đồng.');
                }
            }
        } elseif (in_array($data['receipt_type'], ['deposit', 'contract_payment'], true)) {
            abort(422, 'Loại phiếu thu này yêu cầu hợp đồng.');
        }
    }

    private function validateExpense(array &$data): void
    {
        ExpenseType::query()->whereKey($data['expense_type_id'])->where('is_active', true)->firstOrFail();
        $scope = ExpenseScopeEnum::from($data['scope']);
        if ($scope === ExpenseScopeEnum::VEHICLE) {
            if (empty($data['vehicle_id'])) {
                abort(422, 'Chi phí xe yêu cầu xe.');
            }
            Vehicle::query()->whereKey($data['vehicle_id'])->where('is_active', true)->firstOrFail();
            $data['dispatch_order_id'] = null;
        } elseif ($scope === ExpenseScopeEnum::TRIP) {
            if (empty($data['dispatch_order_id'])) {
                abort(422, 'Chi phí chuyến yêu cầu lệnh điều xe.');
            }
            DispatchOrder::query()->whereKey($data['dispatch_order_id'])->where('is_active', true)->firstOrFail();
            $data['vehicle_id'] = null;
        } else {
            $data['vehicle_id'] = null;
            $data['dispatch_order_id'] = null;
        }
        foreach (['partner_id' => Partner::class, 'driver_id' => Driver::class] as $field => $model) {
            if (! empty($data[$field])) {
                $model::query()->whereKey($data[$field])->where('is_active', true)->firstOrFail();
            }
        }
    }

    private function validatePartnerPayment(array $data): void
    {
        Partner::query()->whereKey($data['partner_id'])->where('is_active', true)->firstOrFail();
        if (! empty($data['dispatch_order_id'])) {
            $order = DispatchOrder::query()->with('tripAssignment.vehicle')->whereKey($data['dispatch_order_id'])->where('is_active', true)->firstOrFail();
            $partnerId = $order->tripAssignment?->partner_id ?? $order->tripAssignment?->vehicle?->partner_id;
            if ((int) $partnerId !== (int) $data['partner_id']) {
                abort(409, 'Đối tác không khớp phân công của lệnh điều xe.');
            }
        }
    }

    private function assertMutable(Model $model): void
    {
        if ((bool) $model->getAttribute('is_locked')) {
            abort(409, 'Chứng từ đã khóa và không thể thay đổi.');
        }
        if (! (bool) $model->getAttribute('is_active')) {
            abort(409, 'Chứng từ đã ngừng hoạt động.');
        }
    }

    private function lock(Model $model, callable $map): array
    {
        return DB::transaction(function () use ($model, $map): array {
            $locked = $model->newQuery()->lockForUpdate()->findOrFail($model->getKey());
            $this->assertMutable($locked);
            $locked->forceFill(['is_locked' => true])->save();

            return $map($locked->fresh());
        });
    }

    private function nextCode(string $model, string $field, string $prefix): string
    {
        $year = now()->format('Y');
        $max = $model::query()->lockForUpdate()->where($field, 'like', $prefix.$year.'%')->pluck($field)->map(fn (string $value) => (int) substr($value, strlen($prefix.$year)))->max() ?? 0;

        return $prefix.$year.str_pad((string) ($max + 1), 5, '0', STR_PAD_LEFT);
    }

    private function receiptData(Receipt $item): array
    {
        $contract = $item->contract;
        $received = $contract ? Receipt::query()->where('is_active', true)->where('contract_id', $contract->id)->sum('amount') : null;

        return ['id' => $item->id, 'receipt_no' => $item->receipt_no, 'customer_id' => $item->customer_id, 'customer_name' => $item->customer?->name, 'contract_id' => $item->contract_id, 'contract_no' => $contract?->contract_no, 'receipt_type' => $item->receipt_type?->value, 'received_at' => $item->received_at?->toISOString(), 'amount' => $item->amount, 'payment_method' => $item->payment_method?->value, 'payer_name' => $item->payer_name, 'description' => $item->description, 'is_locked' => $item->is_locked, 'is_active' => $item->is_active, 'contract_total' => $contract?->total_amount, 'received_total' => $received !== null ? (string) $received : null, 'outstanding_amount' => $contract ? (string) ((float) $contract->total_amount - (float) $received) : null];
    }

    private function expenseData(Expense $item): array
    {
        return ['id' => $item->id, 'expense_no' => $item->expense_no, 'expense_type_id' => $item->expense_type_id, 'expense_type_name' => $item->expenseType?->name, 'scope' => $item->scope?->value, 'vehicle_id' => $item->vehicle_id, 'vehicle_license_plate' => $item->vehicle?->license_plate, 'dispatch_order_id' => $item->dispatch_order_id, 'dispatch_order_no' => $item->dispatchOrder?->order_no, 'partner_id' => $item->partner_id, 'partner_name' => $item->partner?->name, 'driver_id' => $item->driver_id, 'driver_name' => $item->driver?->full_name, 'expense_date' => $item->expense_date?->toISOString(), 'amount' => $item->amount, 'payment_method' => $item->payment_method, 'document_no' => $item->document_no, 'description' => $item->description, 'is_locked' => $item->is_locked, 'is_active' => $item->is_active];
    }

    private function partnerPaymentData(PartnerPayment $item): array
    {
        return ['id' => $item->id, 'payment_no' => $item->payment_no, 'partner_id' => $item->partner_id, 'partner_name' => $item->partner?->name, 'dispatch_order_id' => $item->dispatch_order_id, 'dispatch_order_no' => $item->dispatchOrder?->order_no, 'paid_at' => $item->paid_at?->toISOString(), 'amount' => $item->amount, 'payment_method' => $item->payment_method?->value, 'description' => $item->description, 'is_locked' => $item->is_locked, 'is_active' => $item->is_active];
    }
}
