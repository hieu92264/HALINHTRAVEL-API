<?php

namespace App\Modules\Other\Services;

use App\Modules\Dispatch\Models\DispatchOrder;
use App\Modules\Finance\Models\Expense;
use App\Modules\Finance\Models\PartnerPayment;
use App\Modules\Finance\Models\Receipt;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\Partner;
use App\Modules\Other\Interfaces\DebtReportServiceInterface;
use App\Shared\Enums\DispatchOrderStatusEnum;
use App\Shared\Enums\ExpenseSettlementTypeEnum;
use App\Shared\Enums\OwnershipTypeEnum;
use Illuminate\Support\Collection;

class DebtReportService implements DebtReportServiceInterface
{
    public function customerDebts(?string $fromDate, ?string $toDate): array
    {
        $orders = $this->completedOrders();
        $receipts = Receipt::query()->where('is_active', true)->get();

        return Customer::query()->orderBy('name')->get()->map(function (Customer $customer) use ($orders, $receipts, $fromDate, $toDate): array {
            $customerOrders = $orders->filter(fn (DispatchOrder $order) => (int) $order->tripSchedule?->contract?->customer_id === $customer->id);
            $customerReceipts = $receipts->where('customer_id', $customer->id);
            $opening = (float) $customer->opening_balance + $this->sumBefore($customerOrders, 'customer_amount', 'completed_at', $fromDate) - $this->sumBefore($customerReceipts, 'amount', 'received_at', $fromDate);
            $periodOrders = $this->inPeriod($customerOrders, 'completed_at', $fromDate, $toDate);
            $received = $this->sumInPeriod($customerReceipts, 'amount', 'received_at', $fromDate, $toDate);
            $fixed = $periodOrders->filter(fn (DispatchOrder $order) => $order->tripSchedule?->service_type?->value === 'fixed')->sum('customer_amount');
            $tourism = $periodOrders->filter(fn (DispatchOrder $order) => $order->tripSchedule?->service_type?->value === 'tourism')->sum('customer_amount');
            $other = $periodOrders->sum('customer_amount') - $fixed - $tourism;
            $incurred = $fixed + $tourism + $other;

            return $this->row([
                'customer_id' => $customer->id,
                'code' => $customer->code,
                'name' => $customer->name,
                'is_active' => $customer->is_active,
                'fixed_trip_amount' => $fixed,
                'tourism_trip_amount' => $tourism,
                'other_trip_amount' => $other,
                'opening_balance' => $opening,
                'incurred_amount' => $incurred,
                'received_amount' => $received,
                'closing_balance' => $opening + $incurred - $received,
            ]);
        })->all();
    }

    public function partnerDebts(?string $fromDate, ?string $toDate): array
    {
        $orders = $this->completedOrders();
        $payments = PartnerPayment::query()->where('is_active', true)->get();

        return Partner::query()->orderBy('name')->get()->map(function (Partner $partner) use ($orders, $payments, $fromDate, $toDate): array {
            $partnerOrders = $orders->filter(fn (DispatchOrder $order) => $this->carrierPartnerId($order) === $partner->id);
            $partnerPayments = $payments->where('partner_id', $partner->id);
            $opening = (float) $partner->opening_balance + $this->sumBefore($partnerOrders, 'partner_vehicle_cost', 'completed_at', $fromDate) - $this->sumBefore($partnerPayments, 'amount', 'paid_at', $fromDate);
            $incurred = $this->sumInPeriod($partnerOrders, 'partner_vehicle_cost', 'completed_at', $fromDate, $toDate);
            $paid = $this->sumInPeriod($partnerPayments, 'amount', 'paid_at', $fromDate, $toDate);

            return $this->row([
                'partner_id' => $partner->id,
                'code' => $partner->code,
                'name' => $partner->name,
                'is_active' => $partner->is_active,
                'opening_balance' => $opening,
                'incurred_amount' => $incurred,
                'partner_vehicle_cost' => $incurred,
                'paid_amount' => $paid,
                'closing_balance' => $opening + $incurred - $paid,
            ]);
        })->all();
    }

    public function externalDriverDebts(?string $fromDate, ?string $toDate): array
    {
        $orders = $this->completedOrders()->filter(fn (DispatchOrder $order) => (float) $order->external_driver_cost > 0 && $order->tripAssignment?->driver?->type === OwnershipTypeEnum::PARTNER);
        $payments = Expense::query()->where('is_active', true)->where('settlement_type', ExpenseSettlementTypeEnum::EXTERNAL_DRIVER_PAYMENT->value)->get();

        return Driver::query()->with('partner')->where('type', OwnershipTypeEnum::PARTNER->value)->orderBy('full_name')->get()->map(function (Driver $driver) use ($orders, $payments, $fromDate, $toDate): array {
            $driverOrders = $orders->filter(fn (DispatchOrder $order) => (int) $order->tripAssignment?->driver_id === $driver->id);
            $driverPayments = $payments->where('driver_id', $driver->id);
            $opening = $this->sumBefore($driverOrders, 'external_driver_cost', 'completed_at', $fromDate) - $this->sumBefore($driverPayments, 'amount', 'expense_date', $fromDate);
            $incurred = $this->sumInPeriod($driverOrders, 'external_driver_cost', 'completed_at', $fromDate, $toDate);
            $paid = $this->sumInPeriod($driverPayments, 'amount', 'expense_date', $fromDate, $toDate);

            return $this->row([
                'driver_id' => $driver->id,
                'code' => $driver->code,
                'name' => $driver->full_name,
                'partner_id' => $driver->partner_id,
                'partner_name' => $driver->partner?->name,
                'is_active' => $driver->is_active,
                'opening_balance' => $opening,
                'incurred_amount' => $incurred,
                'external_driver_cost' => $incurred,
                'paid_amount' => $paid,
                'closing_balance' => $opening + $incurred - $paid,
            ]);
        })->all();
    }

    /** @return Collection<int, DispatchOrder> */
    private function completedOrders(): Collection
    {
        return DispatchOrder::query()
            ->with(['tripSchedule.contract', 'tripAssignment.vehicle', 'tripAssignment.driver.partner'])
            ->where('is_active', true)
            ->where('status', DispatchOrderStatusEnum::COMPLETED->value)
            ->get();
    }

    private function carrierPartnerId(DispatchOrder $order): ?int
    {
        return $order->tripAssignment?->partner_id ?? $order->tripAssignment?->vehicle?->partner_id;
    }

    private function sumBefore(Collection $records, string $amountField, string $dateField, ?string $fromDate): float
    {
        if ($fromDate === null) {
            return 0;
        }

        return (float) $records->filter(fn ($record) => $record->{$dateField}?->lt($fromDate))->sum($amountField);
    }

    private function sumInPeriod(Collection $records, string $amountField, string $dateField, ?string $fromDate, ?string $toDate): float
    {
        return (float) $this->inPeriod($records, $dateField, $fromDate, $toDate)->sum($amountField);
    }

    private function inPeriod(Collection $records, string $dateField, ?string $fromDate, ?string $toDate): Collection
    {
        return $records->filter(function ($record) use ($dateField, $fromDate, $toDate): bool {
            $date = $record->{$dateField};

            return $date !== null
                && ($fromDate === null || $date->gte($fromDate))
                && ($toDate === null || $date->lte($toDate.' 23:59:59'));
        });
    }

    /** @param array<string, mixed> $row */
    private function row(array $row): array
    {
        foreach (['opening_balance', 'incurred_amount', 'fixed_trip_amount', 'tourism_trip_amount', 'other_trip_amount', 'partner_vehicle_cost', 'external_driver_cost', 'received_amount', 'paid_amount', 'closing_balance'] as $field) {
            if (array_key_exists($field, $row)) {
                $row[$field] = number_format((float) $row[$field], 2, '.', '');
            }
        }

        return $row;
    }
}
