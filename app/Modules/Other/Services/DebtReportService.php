<?php

namespace App\Modules\Other\Services;

use App\Modules\Contract\Models\Contract;
use App\Modules\Finance\Models\Expense;
use App\Modules\Finance\Models\PartnerPayment;
use App\Modules\Finance\Models\Receipt;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Partner;
use App\Modules\Other\Interfaces\DebtReportServiceInterface;

class DebtReportService implements DebtReportServiceInterface
{
    public function customerDebts(?string $fromDate, ?string $toDate): array
    {
        return Customer::query()->where('is_active', true)->orderBy('name')->get()->map(function (Customer $customer) use ($fromDate, $toDate): array {
            $contracts = Contract::query()->where('is_active', true)->where('customer_id', $customer->id);
            $receipts = Receipt::query()->where('is_active', true)->where('customer_id', $customer->id);
            $opening = (float) $customer->opening_balance + (float) (clone $contracts)->when($fromDate, fn ($q) => $q->whereDate('effective_from', '<', $fromDate))->sum('total_amount') - (float) (clone $receipts)->when($fromDate, fn ($q) => $q->whereDate('received_at', '<', $fromDate))->sum('amount');
            $incurred = (float) (clone $contracts)->when($fromDate, fn ($q) => $q->whereDate('effective_from', '>=', $fromDate))->when($toDate, fn ($q) => $q->whereDate('effective_from', '<=', $toDate))->sum('total_amount');
            $received = (float) (clone $receipts)->when($fromDate, fn ($q) => $q->whereDate('received_at', '>=', $fromDate))->when($toDate, fn ($q) => $q->whereDate('received_at', '<=', $toDate))->sum('amount');

            return ['customer_id' => $customer->id, 'code' => $customer->code, 'name' => $customer->name, 'opening_balance' => number_format($opening, 2, '.', ''), 'incurred_amount' => number_format($incurred, 2, '.', ''), 'contract_amount' => number_format($incurred, 2, '.', ''), 'received_amount' => number_format($received, 2, '.', ''), 'closing_balance' => number_format($opening + $incurred - $received, 2, '.', '')];
        })->all();
    }

    public function partnerDebts(?string $fromDate, ?string $toDate): array
    {
        return Partner::query()->where('is_active', true)->orderBy('name')->get()->map(function (Partner $partner) use ($fromDate, $toDate): array {
            $expenses = Expense::query()->where('is_active', true)->where('partner_id', $partner->id);
            $payments = PartnerPayment::query()->where('is_active', true)->where('partner_id', $partner->id);
            $opening = (float) $partner->opening_balance + (float) (clone $expenses)->when($fromDate, fn ($q) => $q->whereDate('expense_date', '<', $fromDate))->sum('amount') - (float) (clone $payments)->when($fromDate, fn ($q) => $q->whereDate('paid_at', '<', $fromDate))->sum('amount');
            $incurred = (float) (clone $expenses)->when($fromDate, fn ($q) => $q->whereDate('expense_date', '>=', $fromDate))->when($toDate, fn ($q) => $q->whereDate('expense_date', '<=', $toDate))->sum('amount');
            $paid = (float) (clone $payments)->when($fromDate, fn ($q) => $q->whereDate('paid_at', '>=', $fromDate))->when($toDate, fn ($q) => $q->whereDate('paid_at', '<=', $toDate))->sum('amount');

            return ['partner_id' => $partner->id, 'code' => $partner->code, 'name' => $partner->name, 'opening_balance' => number_format($opening, 2, '.', ''), 'incurred_amount' => number_format($incurred, 2, '.', ''), 'paid_amount' => number_format($paid, 2, '.', ''), 'closing_balance' => number_format($opening + $incurred - $paid, 2, '.', '')];
        })->all();
    }
}
