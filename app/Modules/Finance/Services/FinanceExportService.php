<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\Expense;
use App\Modules\Finance\Models\PartnerPayment;
use App\Modules\Finance\Models\Receipt;
use Illuminate\Support\Carbon;

class FinanceExportService
{
    /** @return array{title: string, columns: array<string, string>, rows: array<int, array<string, string>>} */
    public function table(string $table): array
    {
        return match ($table) {
            'receipts' => $this->receipts(),
            'vehicle-expenses' => $this->expenses('vehicle'),
            'general-expenses' => $this->expenses('general'),
            'partner-payments' => $this->partnerPayments(),
        };
    }

    /** @return array{title: string, columns: array<string, string>, rows: array<int, array<string, string>>} */
    private function receipts(): array
    {
        $rows = Receipt::query()->with('customer:id,name')->where('is_active', true)->latest('received_at')->get()
            ->map(fn (Receipt $item) => [
                'receipt_no' => $item->receipt_no,
                'received_at' => $this->date($item->received_at),
                'customer_name' => $item->customer?->name ?? '—',
                'receipt_type' => match ($item->receipt_type?->value) {
                    'deposit' => 'Đặt cọc', 'contract_payment' => 'Thu cước xe chạy', default => 'Khác',
                },
                'payer_name' => $item->payer_name ?: '—',
                'amount' => $this->money($item->amount),
                'payment_method' => $this->paymentMethod($item->payment_method?->value),
            ])->all();

        return ['title' => 'PHIẾU THU KHÁCH HÀNG', 'columns' => ['receipt_no' => 'Số phiếu', 'received_at' => 'Ngày thu', 'customer_name' => 'Khách hàng', 'receipt_type' => 'Loại thu', 'payer_name' => 'Người nộp', 'amount' => 'Số tiền', 'payment_method' => 'Phương thức'], 'rows' => $rows];
    }

    /** @return array{title: string, columns: array<string, string>, rows: array<int, array<string, string>>} */
    private function expenses(string $scope): array
    {
        $rows = Expense::query()->with(['expenseType:id,name,scope', 'vehicle:id,license_plate'])->where('is_active', true)
            ->whereHas('expenseType', fn ($query) => $query->where('scope', $scope))->latest('expense_date')->get()
            ->map(fn (Expense $item) => $scope === 'vehicle'
                ? ['expense_no' => $item->expense_no, 'expense_date' => $this->date($item->expense_date), 'expense_type_name' => $item->expenseType?->name ?? '—', 'vehicle_plate' => $item->vehicle?->license_plate ?? '—', 'paid_by' => $item->paid_by ?: '—', 'description' => $item->description ?: '—', 'amount' => $this->money($item->amount)]
                : ['expense_date' => $this->date($item->expense_date), 'expense_no' => $item->expense_no, 'description' => $item->description ?: '—', 'amount' => $this->money($item->amount)])->all();

        return $scope === 'vehicle'
            ? ['title' => 'BẢNG CHI PHÍ XE', 'columns' => ['expense_no' => 'Số phiếu chi', 'expense_date' => 'Ngày chi', 'expense_type_name' => 'Loại chi phí', 'vehicle_plate' => 'Biển số', 'paid_by' => 'Người chi', 'description' => 'Diễn giải', 'amount' => 'Số tiền'], 'rows' => $rows]
            : ['title' => 'BẢNG CHI PHÍ CHUNG', 'columns' => ['expense_date' => 'Ngày chi', 'expense_no' => 'Số phiếu chi', 'description' => 'Diễn giải', 'amount' => 'Số tiền'], 'rows' => $rows];
    }

    /** @return array{title: string, columns: array<string, string>, rows: array<int, array<string, string>>} */
    private function partnerPayments(): array
    {
        $rows = PartnerPayment::query()->with('partner:id,name')->where('is_active', true)->latest('paid_at')->get()
            ->map(fn (PartnerPayment $item) => ['payment_no' => $item->payment_no, 'paid_at' => $this->date($item->paid_at), 'partner_name' => $item->partner?->name ?? '—', 'recipient_name' => $item->recipient_name ?: '—', 'amount' => $this->money($item->amount), 'payment_method' => $this->paymentMethod($item->payment_method?->value)])->all();

        return ['title' => 'BẢNG CHI TRẢ CHỦ XE', 'columns' => ['payment_no' => 'Số phiếu chi', 'paid_at' => 'Ngày chi', 'partner_name' => 'Đối tác', 'recipient_name' => 'Người nhận', 'amount' => 'Số tiền', 'payment_method' => 'Phương thức'], 'rows' => $rows];
    }

    private function date(mixed $value): string
    {
        return $value ? Carbon::parse($value)->format('d/m/Y') : '—';
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 0, ',', '.');
    }

    private function paymentMethod(?string $value): string
    {
        return $value === 'bank_transfer' ? 'Chuyển khoản' : 'Tiền mặt';
    }
}
