<?php

namespace App\Modules\Contract\Services;

use App\Modules\Contract\DTOs\ContractItemData;
use App\Modules\Contract\DTOs\CreateContractData;
use App\Modules\Contract\DTOs\CreateContractFromQuotationData;
use App\Modules\Contract\DTOs\UpdateContractData;
use App\Modules\Contract\Interfaces\ContractServiceInterface;
use App\Modules\Contract\Models\Contract;
use App\Modules\Dispatch\DTOs\AvailabilityItemData;
use App\Modules\Dispatch\DTOs\CheckAvailabilityData;
use App\Modules\Dispatch\Interfaces\AvailabilityServiceInterface;
use App\Modules\Dispatch\Models\TripSchedule;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Route;
use App\Modules\MasterData\Models\VehicleType;
use App\Modules\Rental\Models\Quotation;
use App\Modules\Rental\Models\RentalRequest;
use App\Shared\Enums\ContractStatusEnum;
use App\Shared\Enums\ContractTypeEnum;
use App\Shared\Enums\QuotationStatusEnum;
use App\Shared\Enums\RentalRequestStatusEnum;
use App\Shared\Enums\TripScheduleStatusEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ContractService implements ContractServiceInterface
{
    public function __construct(private readonly AvailabilityServiceInterface $availabilityService) {}

    public function getList(): array
    {
        return Contract::query()->with(['customer', 'rentalRequest', 'quotation', 'items.vehicleType'])->orderByDesc('id')->get()->map(fn (Contract $contract) => $this->contract($contract))->all();
    }

    public function getDetail(Contract $contract): array
    {
        return $this->contract($contract);
    }

    /** @throws \Throwable */
    public function store(CreateContractData $data): array
    {
        return DB::transaction(function () use ($data): array {
            if ($data->contractType !== ContractTypeEnum::PRINCIPLE || $data->rentalRequestId !== null || $data->quotationId !== null) {
                abort(422, 'Tạo hợp đồng trực tiếp chỉ hỗ trợ hợp đồng nguyên tắc và không liên kết yêu cầu hoặc báo giá.');
            }
            $this->validateRelations($data->customerId, $data->rentalRequestId, $data->quotationId, $data->items);

            return $this->create($data->customerId, $data->rentalRequestId, $data->quotationId, $data->contractType->value, $data->signedDate, $data->effectiveFrom, $data->effectiveTo, $data->depositRequired, $data->paymentTerms, $data->terms, $data->items);
        });
    }

    /** @throws \Throwable */
    public function fromQuotation(CreateContractFromQuotationData $data): array
    {
        return DB::transaction(function () use ($data): array {
            if ($data->contractType !== ContractTypeEnum::TRIP) {
                abort(422, 'Hợp đồng tạo từ báo giá phải là hợp đồng theo chuyến.');
            }

            $quotation = Quotation::query()->with('items')->lockForUpdate()->findOrFail($data->quotationId);
            $request = $quotation->rental_request_id === null
                ? null
                : RentalRequest::query()->with('items')->lockForUpdate()->find($quotation->rental_request_id);
            if (Contract::query()->where('quotation_id', $quotation->id)->exists()) {
                abort(409, 'Báo giá đã có hợp đồng đang mở.');
            }
            if ($quotation->status !== QuotationStatusEnum::APPROVED || $request === null || $request->status !== RentalRequestStatusEnum::ACCEPTED) {
                abort(422, 'Báo giá phải được chấp nhận và gắn với yêu cầu thuê xe đã được chấp nhận.');
            }
            $this->assertTripDateCoverage($request, $data->effectiveFrom, $data->effectiveTo);
            $items = $quotation->items->map(fn ($item) => new ContractItemData($item->route_id, $item->vehicle_type_id, $request->service_type, $item->quantity, $item->unit_price, '0', $request->pickup_location, $request->dropoff_location, $item->description))->all();
            $this->validateRelations($quotation->customer_id, $request->id, $quotation->id, $items);
            $result = $this->create($quotation->customer_id, $request->id, $quotation->id, $data->contractType->value, $data->signedDate, $data->effectiveFrom, $data->effectiveTo, $data->depositRequired, $data->paymentTerms ?? $quotation->payment_terms, $data->terms, $items);
            $request->forceFill(['status' => RentalRequestStatusEnum::CONVERTED])->save();

            return $result;
        });
    }

    /** @throws \Throwable */
    public function update(Contract $contract, UpdateContractData $data): array
    {
        return DB::transaction(function () use ($contract, $data): array {
            $locked = Contract::query()->with('items')->lockForUpdate()->findOrFail($contract->id);
            $this->assertDraft($locked);
            if ($locked->contract_type !== ContractTypeEnum::PRINCIPLE) {
                abort(409, 'Hợp đồng theo chuyến được tạo từ báo giá và không thể chỉnh sửa trực tiếp.');
            }
            $v = $data->values;
            $customerId = $v['customer_id'] ?? $locked->customer_id;
            $requestId = array_key_exists('rental_request_id', $v) ? $v['rental_request_id'] : $locked->rental_request_id;
            $quoteId = array_key_exists('quotation_id', $v) ? $v['quotation_id'] : $locked->quotation_id;
            $items = $data->items ?? $locked->items->map(fn ($item) => new ContractItemData($item->route_id, $item->vehicle_type_id, $item->service_type, $item->quantity, $item->unit_price, $item->driver_wage, $item->pickup_location, $item->dropoff_location, $item->note))->all();
            $from = $v['effective_from'] ?? $locked->effective_from->toDateString();
            $to = array_key_exists('effective_to', $v) ? $v['effective_to'] : $locked->effective_to?->toDateString();
            $this->validateDates($from, $to);
            $this->validateRelations($customerId, $requestId, $quoteId, $items);
            $total = $this->total($items);
            $deposit = (string) ($v['deposit_required'] ?? $locked->deposit_required);
            $this->validateDeposit($deposit, $total);
            $locked->fill([...$v, 'customer_id' => $customerId, 'rental_request_id' => $requestId, 'quotation_id' => $quoteId, 'total_amount' => $total, 'deposit_required' => $deposit])->save();
            if ($data->items !== null) {
                $locked->items()->delete();
                $locked->items()->createMany($this->itemArrays($items));
            }

            return $this->contract($locked->fresh());
        });
    }

    public function delete(Contract $contract): void
    {
        DB::transaction(function () use ($contract): void {
            $locked = Contract::query()->lockForUpdate()->findOrFail($contract->id);
            $this->assertDraft($locked);
            $this->assertNoOperationalRecords($locked);
            $locked->forceFill(['is_active' => false])->save();
        });
    }

    public function activate(Contract $contract): array
    {
        return $this->transition($contract, ContractStatusEnum::DRAFT, ContractStatusEnum::ACTIVE);
    }

    public function complete(Contract $contract): array
    {
        return $this->transition($contract, ContractStatusEnum::ACTIVE, ContractStatusEnum::COMPLETED);
    }

    public function cancel(Contract $contract): array
    {
        return DB::transaction(function () use ($contract): array {
            $locked = Contract::query()->lockForUpdate()->findOrFail($contract->id);
            if (! in_array($locked->status, [ContractStatusEnum::DRAFT, ContractStatusEnum::ACTIVE], true)) {
                abort(409, 'Hợp đồng không thể hủy ở trạng thái hiện tại.');
            } $this->assertNoOperationalRecords($locked);
            $locked->forceFill(['status' => ContractStatusEnum::CANCELLED])->save();

            return $this->contract($locked->fresh());
        });
    }

    private function transition(Contract $contract, ContractStatusEnum $from, ContractStatusEnum $to): array
    {
        return DB::transaction(function () use ($contract, $from, $to): array {
            $locked = Contract::query()->with('items')->lockForUpdate()->findOrFail($contract->id);
            if ($locked->status !== $from) {
                abort(409, 'Hợp đồng không thể chuyển trạng thái hiện tại.');
            } if ($to === ContractStatusEnum::ACTIVE && ($locked->items->isEmpty() || $locked->effective_to?->lt($locked->effective_from))) {
                abort(422, 'Hợp đồng chưa đủ dữ liệu để kích hoạt.');
            }
            if ($to === ContractStatusEnum::ACTIVE && $locked->contract_type === ContractTypeEnum::TRIP) {
                $this->assertTripCapacity($locked);
                $this->generateTripSchedules($locked);
            }
            if ($to === ContractStatusEnum::COMPLETED && $locked->tripSchedules()->whereNotIn('status', ['COMPLETED', 'CANCELLED'])->exists()) {
                abort(409, 'Không thể hoàn thành khi còn lịch chuyến mở.');
            } $locked->forceFill(['status' => $to])->save();

            return $this->contract($locked->fresh());
        });
    }

    /** @param list<ContractItemData> $items */
    private function create(int $customerId, ?int $requestId, ?int $quoteId, string $type, ?string $signed, string $from, ?string $to, string $deposit, ?string $payment, ?string $terms, array $items): array
    {
        $this->validateDates($from, $to);
        $total = $this->total($items);
        $this->validateDeposit($deposit, $total);
        $contract = Contract::create(['contract_no' => $this->nextNo(Carbon::parse($from)->year), 'customer_id' => $customerId, 'rental_request_id' => $requestId, 'quotation_id' => $quoteId, 'contract_type' => $type, 'signed_date' => $signed, 'effective_from' => $from, 'effective_to' => $to, 'total_amount' => $total, 'deposit_required' => $deposit, 'payment_terms' => $payment, 'terms' => $terms, 'status' => ContractStatusEnum::DRAFT]);
        $contract->items()->createMany($this->itemArrays($items));

        return $this->contract($contract->fresh());
    }

    /** @param list<ContractItemData> $items */
    private function validateRelations(int $customerId, ?int $requestId, ?int $quoteId, array $items): void
    {
        if (! Customer::query()->whereKey($customerId)->where('is_active', true)->exists()) {
            abort(422, 'Khách hàng không hợp lệ hoặc đã ngừng hoạt động.');
        } if ($requestId !== null) {
            $r = RentalRequest::query()->findOrFail($requestId);
            if ($r->customer_id !== $customerId) {
                abort(422, 'Yêu cầu thuê xe không thuộc khách hàng của hợp đồng.');
            }
        } if ($quoteId !== null) {
            $q = Quotation::query()->findOrFail($quoteId);
            if ($q->customer_id !== $customerId) {
                abort(422, 'Báo giá không thuộc khách hàng của hợp đồng.');
            }
        } $vehicles = collect($items)->pluck('vehicleTypeId')->unique();
        if (VehicleType::query()->whereIn('id', $vehicles)->where('is_active', true)->count() !== $vehicles->count()) {
            abort(422, 'Loại xe không hợp lệ hoặc đã ngừng hoạt động.');
        } $routes = collect($items)->pluck('routeId')->filter()->unique();
        if (Route::query()->whereIn('id', $routes)->where('is_active', true)->count() !== $routes->count()) {
            abort(422, 'Tuyến không hợp lệ hoặc đã ngừng hoạt động.');
        }
    }

    private function assertTripDateCoverage(RentalRequest $request, string $from, ?string $to): void
    {
        if ($request->start_at === null || $request->end_at === null || $to === null) {
            abort(422, 'Yêu cầu thuê xe và hợp đồng theo chuyến phải có đủ thời gian bắt đầu, kết thúc.');
        }

        if (Carbon::parse($from)->startOfDay()->gt($request->start_at->copy()->startOfDay())
            || Carbon::parse($to)->endOfDay()->lt($request->end_at->copy()->endOfDay())) {
            abort(422, 'Hiệu lực hợp đồng theo chuyến phải bao trùm toàn bộ thời gian của yêu cầu thuê xe.');
        }
    }

    private function assertTripCapacity(Contract $contract): void
    {
        $request = $contract->rental_request_id === null
            ? null
            : RentalRequest::query()->lockForUpdate()->find($contract->rental_request_id);
        if ($request === null || $request->start_at === null || $request->end_at === null) {
            abort(422, 'Hợp đồng theo chuyến phải gắn với yêu cầu thuê xe có thời gian hợp lệ.');
        }

        $items = $contract->items
            ->groupBy('vehicle_type_id')
            ->map(static fn ($group, int $vehicleTypeId): AvailabilityItemData => new AvailabilityItemData(
                vehicleTypeId: $vehicleTypeId,
                quantity: $group->sum('quantity'),
            ))
            ->values()
            ->all();
        $availability = $this->availabilityService->check(new CheckAvailabilityData(
            startAt: $request->start_at->toDateTimeString(),
            endAt: $request->end_at->toDateTimeString(),
            items: $items,
            ownershipType: null,
            partnerId: null,
        ));
        if ($availability['can_fulfill']) {
            return;
        }

        abort(422, 'Không đủ năng lực điều độ để kích hoạt hợp đồng theo chuyến.');
    }

    private function generateTripSchedules(Contract $contract): void
    {
        $request = RentalRequest::query()->findOrFail($contract->rental_request_id);
        if ($request->start_at === null || $request->end_at === null) {
            abort(422, 'Không thể sinh lịch chuyến khi yêu cầu thuê xe thiếu thời gian.');
        }
        foreach ($contract->items as $item) {
            for ($index = 0; $index < $item->quantity; $index++) {
                TripSchedule::create([
                    'schedule_no' => $this->nextTripScheduleNo($request->start_at),
                    'contract_id' => $contract->id,
                    'contract_item_id' => $item->id,
                    'service_type' => $item->service_type->value,
                    'route_id' => $item->route_id,
                    'scheduled_start_at' => $request->start_at,
                    'scheduled_end_at' => $request->end_at,
                    'pickup_location' => $item->pickup_location,
                    'dropoff_location' => $item->dropoff_location,
                    'required_vehicle_type_id' => $item->vehicle_type_id,
                    'status' => TripScheduleStatusEnum::PLANNED,
                    'note' => $item->note,
                ]);
            }
        }
    }

    private function nextTripScheduleNo(Carbon $at): string
    {
        $prefix = 'LT'.$at->format('Ymd');
        $highest = TripSchedule::query()->where('schedule_no', 'like', $prefix.'%')->lockForUpdate()->pluck('schedule_no')
            ->reduce(static fn (int $carry, string $number): int => preg_match('/^'.preg_quote($prefix, '/').'(\\d{4,})$/', $number, $matches) === 1 ? max($carry, (int) $matches[1]) : $carry, 0);

        return $prefix.str_pad((string) ($highest + 1), 4, '0', STR_PAD_LEFT);
    }

    /** @param list<ContractItemData> $items */
    private function total(array $items): string
    {
        return collect($items)->reduce(fn (string $sum, ContractItemData $item) => bcadd($sum, bcmul($item->unitPrice, (string) $item->quantity, 2), 2), '0.00');
    }

    /** @param list<ContractItemData> $items @return list<array<string,mixed>> */
    private function itemArrays(array $items): array
    {
        return array_map(fn (ContractItemData $i) => ['route_id' => $i->routeId, 'vehicle_type_id' => $i->vehicleTypeId, 'service_type' => $i->serviceType->value, 'quantity' => $i->quantity, 'unit_price' => $i->unitPrice, 'driver_wage' => $i->driverWage, 'pickup_location' => $i->pickupLocation, 'dropoff_location' => $i->dropoffLocation, 'note' => $i->note], $items);
    }

    private function validateDates(string $from, ?string $to): void
    {
        if ($to !== null && Carbon::parse($to)->lt(Carbon::parse($from))) {
            abort(422, 'Ngày kết thúc hiệu lực không được sớm hơn ngày bắt đầu.');
        }
    }

    private function validateDeposit(string $deposit, string $total): void
    {
        if (bccomp($deposit, $total, 2) === 1) {
            abort(422, 'Tiền đặt cọc không được lớn hơn tổng giá trị hợp đồng.');
        }
    }

    private function assertDraft(Contract $contract): void
    {
        if ($contract->status !== ContractStatusEnum::DRAFT) {
            abort(409, 'Chỉ hợp đồng nháp mới được chỉnh sửa.');
        }
    }

    private function assertNoOperationalRecords(Contract $contract): void
    {
        if ($contract->tripSchedules()->exists() || $contract->receipts()->exists()) {
            abort(409, 'Không thể thao tác khi hợp đồng đã có lịch chuyến hoặc chứng từ.');
        }
    }

    private function nextNo(int $year): string
    {
        $prefix = 'HD'.$year;
        $highest = Contract::query()->where('contract_no', 'like', $prefix.'%')->lockForUpdate()->pluck('contract_no')->reduce(static fn (int $carry, string $no): int => preg_match('/^'.preg_quote($prefix, '/').'(\\d{4,})$/', $no, $m) === 1 ? max($carry, (int) $m[1]) : $carry, 0);

        return $prefix.str_pad((string) ($highest + 1), 4, '0', STR_PAD_LEFT);
    }

    private function contract(Contract $contract): array
    {
        $contract->loadMissing(['customer:id,name', 'rentalRequest:id,request_no,status', 'quotation:id,quotation_no,status', 'items.vehicleType:id,name', 'items.route:id,name']);

        return ['id' => $contract->id, 'contract_no' => $contract->contract_no, 'customer_id' => $contract->customer_id, 'customer_name' => $contract->customer?->name, 'rental_request_id' => $contract->rental_request_id, 'rental_request_no' => $contract->rentalRequest?->request_no, 'quotation_id' => $contract->quotation_id, 'quotation_no' => $contract->quotation?->quotation_no, 'contract_type' => $contract->contract_type?->value, 'signed_date' => $contract->signed_date?->toDateString(), 'effective_from' => $contract->effective_from?->toDateString(), 'effective_to' => $contract->effective_to?->toDateString(), 'total_amount' => $contract->total_amount, 'deposit_required' => $contract->deposit_required, 'payment_terms' => $contract->payment_terms, 'terms' => $contract->terms, 'status' => $contract->status?->value, 'is_active' => $contract->is_active, 'items' => $contract->items->map(fn ($i) => ['id' => $i->id, 'route_id' => $i->route_id, 'route_name' => $i->route?->name, 'vehicle_type_id' => $i->vehicle_type_id, 'vehicle_type_name' => $i->vehicleType?->name, 'service_type' => $i->service_type?->value, 'quantity' => $i->quantity, 'unit_price' => $i->unit_price, 'driver_wage' => $i->driver_wage, 'pickup_location' => $i->pickup_location, 'dropoff_location' => $i->dropoff_location, 'note' => $i->note])->all()];
    }
}
