<?php

namespace App\Modules\Other\Services;

use App\Modules\Finance\Models\Expense;
use App\Modules\Finance\Models\PartnerPayment;
use App\Modules\Finance\Models\Receipt;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Partner;
use App\Modules\Other\Interfaces\DebtReportServiceInterface;
use App\Modules\VehicleOperations\Models\TouristVehicle;
use App\Modules\VehicleOperations\Models\WorkerVehicle;
use Illuminate\Support\Collection;

class DebtReportService implements DebtReportServiceInterface
{
    public function customerDebts(?string $fromDate, ?string $toDate): array
    {
        $runs = $this->completedRuns();
        $receipts = Receipt::query()->where('is_active', true)->get();

        return Customer::query()->orderBy('name')->get()->map(function (Customer $customer) use ($runs, $receipts, $fromDate, $toDate): array {
            $customerRuns = $runs->filter(fn (array $run) => $run['customer_id'] === $customer->id);
            $customerReceipts = $receipts->where('customer_id', $customer->id);
            $opening = (float) $customer->opening_balance + $this->sumBefore($customerRuns, 'amount', 'date', $fromDate) - $this->sumBefore($customerReceipts, 'amount', 'received_at', $fromDate);
            $period = $this->inPeriod($customerRuns, 'date', $fromDate, $toDate);
            $fixed = $period->where('kind', 'worker')->sum('amount');
            $tourism = $period->where('kind', 'tourist')->sum('amount');
            $received = $this->sumInPeriod($customerReceipts, 'amount', 'received_at', $fromDate, $toDate);

            return $this->moneyRow(['customer_id' => $customer->id, 'code' => $customer->code, 'name' => $customer->name, 'is_active' => $customer->is_active, 'opening_balance' => $opening, 'fixed_trip_amount' => $fixed, 'tourism_trip_amount' => $tourism, 'incurred_amount' => $fixed + $tourism, 'received_amount' => $received, 'closing_balance' => $opening + $fixed + $tourism - $received]);
        })->all();
    }

    public function partnerDebts(?string $fromDate, ?string $toDate): array
    {
        $runs = $this->completedRuns()->filter(fn (array $run) => $run['partner_id'] !== null);
        $payments = PartnerPayment::query()->where('is_active', true)->get();

        return Partner::query()->orderBy('name')->get()->map(function (Partner $partner) use ($runs, $payments, $fromDate, $toDate): array {
            $partnerRuns = $runs->filter(fn (array $run) => $run['partner_id'] === $partner->id);
            $partnerPayments = $payments->where('partner_id', $partner->id);
            $opening = (float) $partner->opening_balance + $this->sumBefore($partnerRuns, 'partner_vehicle_cost', 'date', $fromDate) - $this->sumBefore($partnerPayments, 'amount', 'paid_at', $fromDate);
            $incurred = $this->sumInPeriod($partnerRuns, 'partner_vehicle_cost', 'date', $fromDate, $toDate);
            $paid = $this->sumInPeriod($partnerPayments, 'amount', 'paid_at', $fromDate, $toDate);

            return $this->moneyRow(['partner_id' => $partner->id, 'code' => $partner->code, 'name' => $partner->name, 'is_active' => $partner->is_active, 'opening_balance' => $opening, 'incurred_amount' => $incurred, 'partner_vehicle_cost' => $incurred, 'paid_amount' => $paid, 'closing_balance' => $opening + $incurred - $paid]);
        })->all();
    }

    public function externalDriverDebts(?string $fromDate, ?string $toDate): array
    {
        $runs = $this->completedRuns()->filter(fn (array $run) => $run['is_hired_driver'] && $run['hired_driver_name'] !== null);
        $expenses = Expense::query()->where('is_active', true)->whereNotNull('hired_driver_name')->get();
        $names = $runs->pluck('hired_driver_name')->merge($expenses->pluck('hired_driver_name'))->filter()->unique()->sort()->values();

        return $names->map(function (string $name) use ($runs, $expenses, $fromDate, $toDate): array {
            $driverRuns = $runs->where('hired_driver_name', $name);
            $payments = $expenses->where('hired_driver_name', $name);
            $opening = $this->sumBefore($driverRuns, 'hired_driver_cost', 'date', $fromDate) - $this->sumBefore($payments, 'amount', 'expense_date', $fromDate);
            $incurred = $this->sumInPeriod($driverRuns, 'hired_driver_cost', 'date', $fromDate, $toDate);
            $paid = $this->sumInPeriod($payments, 'amount', 'expense_date', $fromDate, $toDate);

            return $this->moneyRow(['name' => $name, 'opening_balance' => $opening, 'incurred_amount' => $incurred, 'external_driver_cost' => $incurred, 'paid_amount' => $paid, 'closing_balance' => $opening + $incurred - $paid]);
        })->all();
    }

    public function customerDebtDetails(int $customerId, string $fromDate, string $toDate): array
    {
        $customer = Customer::query()->findOrFail($customerId);
        $runs = $this->completedRuns()->where('customer_id', $customerId);
        $receipts = Receipt::query()->where('is_active', true)->where('customer_id', $customerId)->get();
        $opening = (float) $customer->opening_balance + $this->sumBefore($runs, 'amount', 'date', $fromDate) - $this->sumBefore($receipts, 'amount', 'received_at', $fromDate);
        $entries = $this->inPeriod($runs, 'date', $fromDate, $toDate)->map(fn (array $run) => ['date' => $run['date'], 'document_no' => $run['run_no'], 'type' => $run['kind'] === 'worker' ? 'Xe công nhân hoàn tất' : 'Xe du lịch hoàn tất', 'journey' => $run['journey'], 'vehicle_plate' => $run['license_plate'], 'pickup_time' => $run['pickup_time'], 'return_time' => $run['return_time'], 'debit' => $run['amount'], 'credit' => 0, 'note' => $run['note']]);
        $entries = $entries->concat($this->inPeriod($receipts, 'received_at', $fromDate, $toDate)->map(fn (Receipt $item) => ['date' => $item->received_at, 'document_no' => $item->receipt_no, 'type' => 'Phiếu thu khách hàng', 'journey' => null, 'vehicle_plate' => null, 'pickup_time' => null, 'return_time' => null, 'debit' => 0, 'credit' => (float) $item->amount, 'note' => $item->description]));

        return $this->detail($customer->name, $opening, $entries, $fromDate);
    }

    public function partnerDebtDetails(int $partnerId, string $fromDate, string $toDate): array
    {
        $partner = Partner::query()->findOrFail($partnerId);
        $runs = $this->completedRuns()->where('partner_id', $partnerId);
        $payments = PartnerPayment::query()->where('is_active', true)->where('partner_id', $partnerId)->get();
        $opening = (float) $partner->opening_balance + $this->sumBefore($runs, 'partner_vehicle_cost', 'date', $fromDate) - $this->sumBefore($payments, 'amount', 'paid_at', $fromDate);
        $entries = $this->inPeriod($runs, 'date', $fromDate, $toDate)->map(fn (array $run) => ['date' => $run['date'], 'document_no' => $run['run_no'], 'type' => 'Tiền thuê xe', 'journey' => $run['journey'], 'vehicle_plate' => $run['license_plate'], 'pickup_time' => $run['pickup_time'], 'return_time' => $run['return_time'], 'debit' => $run['partner_vehicle_cost'], 'credit' => 0, 'note' => $run['note']]);
        $entries = $entries->concat($this->inPeriod($payments, 'paid_at', $fromDate, $toDate)->map(fn (PartnerPayment $item) => ['date' => $item->paid_at, 'document_no' => $item->payment_no, 'type' => 'Chi trả đối tác', 'journey' => null, 'vehicle_plate' => null, 'pickup_time' => null, 'return_time' => null, 'debit' => 0, 'credit' => (float) $item->amount, 'note' => $item->description]));

        return $this->detail($partner->name, $opening, $entries, $fromDate);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function completedRuns(): Collection
    {
        $worker = WorkerVehicle::query()->where('is_active', true)->where('status', 'completed')->get()->map(fn (WorkerVehicle $run) => $this->runRow($run, 'worker'));
        $tourist = TouristVehicle::query()->where('is_active', true)->where('status', 'completed')->get()->map(fn (TouristVehicle $run) => $this->runRow($run, 'tourist'));

        return $worker->concat($tourist);
    }

    private function runRow(WorkerVehicle|TouristVehicle $run, string $kind): array
    {
        return ['kind' => $kind, 'customer_id' => $run->customer_id, 'partner_id' => $run->partner_id, 'run_no' => $run->run_no, 'journey' => $run instanceof TouristVehicle ? $run->journey : $run->shift_name, 'license_plate' => $run->license_plate, 'pickup_time' => $run->pickup_time, 'return_time' => $run->return_time, 'amount' => (float) $run->freight_amount, 'partner_vehicle_cost' => (float) $run->partner_vehicle_cost, 'hired_driver_cost' => (float) $run->hired_driver_cost, 'is_hired_driver' => $run->is_hired_driver, 'hired_driver_name' => $run->hired_driver_name, 'date' => $run->actual_completed_at ?? $run->completed_at, 'note' => $run->note];
    }

    private function detail(string $subjectName, float $opening, Collection $entries, string $fromDate): array
    {
        $balance = $opening;
        $rows = $entries->sortBy('date')->values()->map(function (array $entry, int $index) use (&$balance): array {
            $balance += (float) $entry['debit'] - (float) $entry['credit'];

            return [...$entry, 'stt' => $index + 1, 'date' => $entry['date']?->toDateString(), 'debit' => $this->money((float) $entry['debit']), 'credit' => $this->money((float) $entry['credit']), 'balance' => $this->money($balance)];
        })->all();
        array_unshift($rows, ['stt' => 0, 'date' => $fromDate, 'document_no' => null, 'type' => 'Nợ đầu kỳ', 'journey' => null, 'vehicle_plate' => null, 'pickup_time' => null, 'return_time' => null, 'debit' => '0.00', 'credit' => '0.00', 'balance' => $this->money($opening), 'note' => null]);

        return ['subject_name' => $subjectName, 'opening_balance' => $this->money($opening), 'rows' => $rows, 'closing_balance' => $this->money($balance)];
    }

    private function sumBefore(Collection $items, string $amount, string $date, ?string $from): float
    {
        return $from === null ? 0 : (float) $items->filter(fn ($item) => $this->value($item, $date)?->lt($from))->sum($amount);
    }

    private function sumInPeriod(Collection $items, string $amount, string $date, ?string $from, ?string $to): float
    {
        return (float) $this->inPeriod($items, $date, $from, $to)->sum($amount);
    }

    private function inPeriod(Collection $items, string $date, ?string $from, ?string $to): Collection
    {
        return $items->filter(fn ($item) => $this->value($item, $date) !== null && ($from === null || $this->value($item, $date)->gte($from)) && ($to === null || $this->value($item, $date)->lte($to.' 23:59:59')));
    }

    private function value(mixed $item, string $key): mixed
    {
        return is_array($item) ? ($item[$key] ?? null) : $item->{$key};
    }

    private function moneyRow(array $row): array
    {
        foreach (['opening_balance', 'fixed_trip_amount', 'tourism_trip_amount', 'incurred_amount', 'received_amount', 'partner_vehicle_cost', 'external_driver_cost', 'paid_amount', 'closing_balance'] as $key) {
            if (array_key_exists($key, $row)) {
                $row[$key] = $this->money((float) $row[$key]);
            }
        }

        return $row;
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
