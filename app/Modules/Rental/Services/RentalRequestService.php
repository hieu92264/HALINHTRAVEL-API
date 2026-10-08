<?php

namespace App\Modules\Rental\Services;

use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Route;
use App\Modules\MasterData\Models\VehicleType;
use App\Modules\Rental\DTOs\CreateRentalRequestData;
use App\Modules\Rental\DTOs\RentalRequestItemData;
use App\Modules\Rental\DTOs\UpdateRentalRequestData;
use App\Modules\Rental\Interfaces\RentalRequestServiceInterface;
use App\Modules\Rental\Models\Quotation;
use App\Modules\Rental\Models\RentalRequest;
use App\Shared\Enums\QuotationStatusEnum;
use App\Shared\Enums\RentalRequestStatusEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RentalRequestService implements RentalRequestServiceInterface
{
    public function getList(): array
    {
        return RentalRequest::query()
            ->with(['items'])
            ->orderBy('id')
            ->get()
            ->map(fn (RentalRequest $rentalRequest): array => $this->rentalRequest($rentalRequest))
            ->all();
    }

    public function rentalRequest(RentalRequest $rentalRequest): array
    {
        $rentalRequest->loadMissing([
            'items:id,rental_request_id,vehicle_type_id,route_id,quantity,note',
            'items.vehicleType:id,name',
            'items.route:id,name',
            'customer:id,code,type,name,phone,email,address,contact_name',
        ]);

        return [
            'id' => $rentalRequest->id,
            'request_no' => $rentalRequest->request_no,
            'is_active' => $rentalRequest->is_active,
            'user_name_created' => $rentalRequest->user_name_created,
            'user_name_updated' => $rentalRequest->user_name_updated,
            'created_at' => $rentalRequest->created_at?->toISOString(),
            'updated_at' => $rentalRequest->updated_at?->toISOString(),
            'customer_id' => $rentalRequest->customer_id,
            'customer_name' => $rentalRequest->customer?->name,
            'customer' => $rentalRequest->customer === null ? null : [
                'id' => $rentalRequest->customer->id,
                'code' => $rentalRequest->customer->code,
                'type' => $rentalRequest->customer->type?->value,
                'name' => $rentalRequest->customer->name,
                'phone' => $rentalRequest->customer->phone,
                'email' => $rentalRequest->customer->email,
                'address' => $rentalRequest->customer->address,
                'contact_name' => $rentalRequest->customer->contact_name,
            ],
            'items' => $rentalRequest->items
                ->map(static fn ($item): array => [
                    'id' => $item->id,
                    'vehicle_type_id' => $item->vehicle_type_id,
                    'vehicle_type_name' => $item->vehicleType?->name,
                    'quantity' => $item->quantity,
                    'route_id' => $item->route_id,
                    'route_name' => $item->route?->name,
                    'note' => $item->note,
                ])
                ->values()
                ->all(),
            'source' => $rentalRequest->source,
            'requested_at' => $rentalRequest->requested_at,
            'service_type' => $rentalRequest->service_type,
            'pickup_location' => $rentalRequest->pickup_location,
            'dropoff_location' => $rentalRequest->dropoff_location,
            'start_at' => $rentalRequest->start_at,
            'end_at' => $rentalRequest->end_at,
            'note' => $rentalRequest->note,
            'status' => $rentalRequest->status,
        ];
    }

    public function getDetail(RentalRequest $rentalRequest): array
    {
        return $this->rentalRequest($rentalRequest);
    }

    /** @throws \Throwable */
    public function store(CreateRentalRequestData $data): array
    {
        return DB::transaction(function () use ($data): array {
            $customer = Customer::query()
                ->whereKey($data->customer_id)
                ->where('is_active', true)
                ->first();

            if ($customer === null) {
                abort(422, 'Khách hàng không tồn tại hoặc đã ngừng hoạt động.');
            }

            $items = $data->items;
            $vehicleTypeIds = collect($items)
                ->map(static fn ($item): int => $item->vehicleTypeId)
                ->unique()
                ->values();

            if (VehicleType::query()
                ->whereIn('id', $vehicleTypeIds)
                ->where('is_active', true)
                ->count() !== $vehicleTypeIds->count()) {
                abort(422, 'Loại xe không tồn tại hoặc đã ngừng hoạt động.');
            }

            $itemsWithRoute = collect($items)->filter(static fn ($item): bool => $item->routeId !== null);
            $routeIds = $itemsWithRoute
                ->map(static fn ($item): int => $item->routeId)
                ->unique()
                ->values();

            $route = null;
            if ($routeIds->isNotEmpty()) {
                if ($routeIds->count() !== 1 || $itemsWithRoute->count() !== count($items)) {
                    abort(422, 'Tất cả hạng mục phải dùng cùng một tuyến hoặc đều không chọn tuyến.');
                }

                $route = Route::query()
                    ->whereKey($routeIds->first())
                    ->where('is_active', true)
                    ->where(static function ($query) use ($customer): void {
                        $query->whereNull('customer_id')
                            ->orWhere('customer_id', $customer->id);
                    })
                    ->first();

                if ($route === null) {
                    abort(422, 'Tuyến không hợp lệ cho khách hàng này hoặc đã ngừng hoạt động.');
                }
            }

            $rentalRequest = RentalRequest::create([
                ...$data->toArray(),
                'request_no' => $this->nextRequestNo(Carbon::parse($data->requested_at)->year),
                'pickup_location' => $route?->pickup_location ?? $data->pickup_location,
                'dropoff_location' => $route?->dropoff_location ?? $data->dropoff_location,
                'status' => RentalRequestStatusEnum::NEW,
            ]);

            $rentalRequest->items()->createMany(array_map(
                static fn ($item): array => $item->toArray(),
                $items,
            ));

            return $this->rentalRequest($rentalRequest->fresh());
        });
    }

    /** @throws \Throwable */
    public function update(RentalRequest $rentalRequest, UpdateRentalRequestData $data): array
    {
        return DB::transaction(function () use ($rentalRequest, $data): array {
            $lockedRequest = RentalRequest::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($rentalRequest->id);

            if ($lockedRequest->status !== RentalRequestStatusEnum::NEW) {
                abort(409, 'Chỉ yêu cầu thuê xe mới tiếp nhận mới được cập nhật.');
            }

            $changes = $data->toArray();
            unset($changes['items']);

            $items = $data->items ?? $lockedRequest->items
                ->map(static fn ($item): RentalRequestItemData => new RentalRequestItemData(
                    vehicleTypeId: $item->vehicle_type_id,
                    quantity: $item->quantity,
                    routeId: $item->route_id,
                    note: $item->note,
                ))
                ->all();

            $customerId = array_key_exists('customer_id', $changes)
                ? $changes['customer_id']
                : $lockedRequest->customer_id;
            [, $route] = $this->resolveJourney($customerId, $items);

            $pickupLocation = array_key_exists('pickup_location', $changes)
                ? $changes['pickup_location']
                : $lockedRequest->pickup_location;
            $dropoffLocation = array_key_exists('dropoff_location', $changes)
                ? $changes['dropoff_location']
                : $lockedRequest->dropoff_location;
            $startAt = array_key_exists('start_at', $changes)
                ? $changes['start_at']
                : $lockedRequest->start_at;
            $endAt = array_key_exists('end_at', $changes)
                ? $changes['end_at']
                : $lockedRequest->end_at;

            $this->validateJourneyHeader($route, $pickupLocation, $dropoffLocation, $startAt, $endAt);

            $lockedRequest->fill([
                ...$changes,
                'pickup_location' => $route?->pickup_location ?? $pickupLocation,
                'dropoff_location' => $route?->dropoff_location ?? $dropoffLocation,
            ])->save();

            if ($data->items !== null) {
                $lockedRequest->items()->delete();
                $lockedRequest->items()->createMany(array_map(
                    static fn (RentalRequestItemData $item): array => $item->toArray(),
                    $items,
                ));
            }

            return $this->rentalRequest($lockedRequest->fresh());
        });
    }

    public function delete(int $id): array
    {
        $rentalRequest = RentalRequest::query()->lockForUpdate()->findOrFail($id);
        if ($rentalRequest->status !== RentalRequestStatusEnum::NEW) {
            abort(409, 'Chỉ yêu cầu thuê xe ở trạng thái mới được ngừng hoạt động.');
        }

        $rentalRequest->forceFill(['is_active' => false])->save();

        return $this->rentalRequest($rentalRequest->fresh());
    }

    public function markQuoted(int $id): array
    {
        $rentalRequest = RentalRequest::query()->lockForUpdate()->findOrFail($id);
        if ($rentalRequest->status === RentalRequestStatusEnum::QUOTED) {
            return $this->rentalRequest($rentalRequest);
        }
        if ($rentalRequest->status !== RentalRequestStatusEnum::NEW) {
            abort(409, 'Yêu cầu thuê xe không thể chuyển sang trạng thái đã báo giá.');
        }
        if (! $rentalRequest->quotations()->where('status', 'sent')->exists()) {
            abort(422, 'Cần có ít nhất một báo giá đã gửi trước khi chuyển yêu cầu sang đã báo giá.');
        }

        $rentalRequest->forceFill(['status' => RentalRequestStatusEnum::QUOTED])->save();

        return $this->rentalRequest($rentalRequest->fresh());
    }

    public function acceptQuoted(int $id): array
    {
        abort(409, 'Hãy ghi nhận phản hồi chấp nhận trên báo giá để chấp nhận yêu cầu thuê xe.');
    }

    public function rejectQuoted(int $id): array
    {
        return DB::transaction(function () use ($id): array {
            $rentalRequest = RentalRequest::query()->lockForUpdate()->findOrFail($id);
            if (! in_array($rentalRequest->status, [RentalRequestStatusEnum::NEW, RentalRequestStatusEnum::QUOTED], true)) {
                abort(409, 'Yêu cầu thuê xe không thể bị từ chối ở trạng thái hiện tại.');
            }
            $rentalRequest->forceFill(['status' => RentalRequestStatusEnum::REJECTED])->save();

            Quotation::query()
                ->where('rental_request_id', $rentalRequest->id)
                ->whereIn('status', [QuotationStatusEnum::DRAFT->value, QuotationStatusEnum::SENT->value])
                ->update([
                    'status' => QuotationStatusEnum::SUPERSEDED->value,
                    'updated_at' => now(),
                ]);

            return $this->rentalRequest($rentalRequest->fresh());
        });
    }

    /**
     * @param  list<RentalRequestItemData>  $items
     * @return array{Customer, Route|null}
     */
    private function resolveJourney(int $customerId, array $items): array
    {
        $customer = Customer::query()
            ->whereKey($customerId)
            ->where('is_active', true)
            ->first();

        if ($customer === null) {
            abort(422, 'Khách hàng không tồn tại hoặc đã ngừng hoạt động.');
        }

        $vehicleTypeIds = collect($items)
            ->map(static fn (RentalRequestItemData $item): int => $item->vehicleTypeId)
            ->unique()
            ->values();

        if (VehicleType::query()
            ->whereIn('id', $vehicleTypeIds)
            ->where('is_active', true)
            ->count() !== $vehicleTypeIds->count()) {
            abort(422, 'Loại xe không tồn tại hoặc đã ngừng hoạt động.');
        }

        $itemsWithRoute = collect($items)
            ->filter(static fn (RentalRequestItemData $item): bool => $item->routeId !== null);
        $routeIds = $itemsWithRoute
            ->map(static fn (RentalRequestItemData $item): int => $item->routeId)
            ->unique()
            ->values();

        if ($routeIds->isEmpty()) {
            return [$customer, null];
        }

        if ($routeIds->count() !== 1 || $itemsWithRoute->count() !== count($items)) {
            abort(422, 'Tất cả hạng mục phải dùng cùng một tuyến hoặc đều không chọn tuyến.');
        }

        $route = Route::query()
            ->whereKey($routeIds->first())
            ->where('is_active', true)
            ->where(static function ($query) use ($customer): void {
                $query->whereNull('customer_id')
                    ->orWhere('customer_id', $customer->id);
            })
            ->first();

        if ($route === null) {
            abort(422, 'Tuyến không hợp lệ cho khách hàng này hoặc đã ngừng hoạt động.');
        }

        return [$customer, $route];
    }

    private function validateJourneyHeader(
        ?Route $route,
        mixed $pickupLocation,
        mixed $dropoffLocation,
        mixed $startAt,
        mixed $endAt,
    ): void {
        if ($startAt === null) {
            abort(422, 'Thời gian khởi hành là bắt buộc.');
        }

        if ($route === null && (blank($pickupLocation) || blank($dropoffLocation))) {
            abort(422, 'Điểm đón và điểm trả là bắt buộc khi nhập hành trình.');
        }

        if ($endAt !== null && Carbon::parse($endAt)->lt(Carbon::parse($startAt))) {
            abort(422, 'Thời gian kết thúc không được sớm hơn thời gian khởi hành.');
        }
    }

    private function nextRequestNo(int $year): string
    {
        $prefix = 'YC'.$year;

        $highestSequence = RentalRequest::query()
            ->where('request_no', 'like', $prefix.'%')
            ->lockForUpdate()
            ->pluck('request_no')
            ->reduce(static function (int $highestSequence, string $requestNo) use ($prefix): int {
                if (preg_match('/^'.preg_quote($prefix, '/').'(\d{4,})$/', $requestNo, $matches) !== 1) {
                    return $highestSequence;
                }

                return max($highestSequence, (int) $matches[1]);
            }, 0);

        return $prefix.str_pad((string) ($highestSequence + 1), 4, '0', STR_PAD_LEFT);
    }
}
