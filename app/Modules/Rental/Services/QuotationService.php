<?php

namespace App\Modules\Rental\Services;

use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Route;
use App\Modules\MasterData\Models\VehicleType;
use App\Modules\Rental\DTOs\CreateQuotationData;
use App\Modules\Rental\DTOs\QuotationItemData;
use App\Modules\Rental\DTOs\UpdateQuotationData;
use App\Modules\Rental\Interfaces\QuotationServiceInterface;
use App\Modules\Rental\Mail\QuotationSentMail;
use App\Modules\Rental\Models\Quotation;
use App\Modules\Rental\Models\QuotationResponseToken;
use App\Modules\Rental\Models\RentalRequest;
use App\Shared\Enums\QuotationStatusEnum;
use App\Shared\Enums\RentalRequestStatusEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class QuotationService implements QuotationServiceInterface
{
    public function getList(): array
    {
        return Quotation::query()->with(['customer', 'items.vehicleType'])->orderByDesc('id')->get()
            ->map(fn (Quotation $quotation): array => $this->quotation($quotation))->all();
    }

    public function getDetail(Quotation $quotation): array
    {
        return $this->quotation($quotation);
    }

    /** @throws \Throwable */
    public function store(CreateQuotationData $data): array
    {
        return DB::transaction(function () use ($data): array {
            $this->ensureCustomerAndRelations($data->customerId, $data->rentalRequestId, $data->items);
            $amounts = $this->amounts($data->items, $data->discountAmount);
            $quotation = Quotation::create([
                'quotation_no' => $this->nextQuotationNo(Carbon::parse($data->quotationDate)->year),
                'rental_request_id' => $data->rentalRequestId,
                'customer_id' => $data->customerId,
                'quotation_date' => $data->quotationDate,
                'valid_until' => $data->validUntil,
                'discount_amount' => $amounts['discount'],
                'subtotal' => $amounts['subtotal'],
                'total_amount' => $amounts['total'],
                'payment_terms' => $data->paymentTerms,
                'status' => QuotationStatusEnum::DRAFT,
            ]);
            $quotation->items()->createMany($amounts['items']);

            return $this->quotation($quotation->fresh());
        });
    }

    /** @throws \Throwable */
    public function update(Quotation $quotation, UpdateQuotationData $data): array
    {
        return DB::transaction(function () use ($quotation, $data): array {
            $locked = Quotation::query()->with('items')->lockForUpdate()->findOrFail($quotation->id);
            $this->assertDraft($locked);
            $values = $data->values;
            $customerId = $values['customer_id'] ?? $locked->customer_id;
            $requestId = array_key_exists('rental_request_id', $values) ? $values['rental_request_id'] : $locked->rental_request_id;
            $quotationDate = $values['quotation_date'] ?? $locked->quotation_date->toDateString();
            $validUntil = array_key_exists('valid_until', $values) ? $values['valid_until'] : $locked->valid_until?->toDateString();
            if ($validUntil !== null && Carbon::parse($validUntil)->lt(Carbon::parse($quotationDate))) {
                abort(422, 'Ngày hết hiệu lực không được sớm hơn ngày báo giá.');
            }
            $items = $data->items ?? $locked->items->map(static fn ($item): QuotationItemData => new QuotationItemData(
                $item->route_id, $item->vehicle_type_id, $item->description, $item->quantity, $item->unit_price,
            ))->all();
            $this->ensureCustomerAndRelations($customerId, $requestId, $items);
            $discount = (string) ($values['discount_amount'] ?? $locked->discount_amount);
            $amounts = $this->amounts($items, $discount);
            $locked->fill([
                ...$values,
                'customer_id' => $customerId,
                'rental_request_id' => $requestId,
                'discount_amount' => $amounts['discount'],
                'subtotal' => $amounts['subtotal'],
                'total_amount' => $amounts['total'],
            ])->save();
            if ($data->items !== null) {
                $locked->items()->delete();
                $locked->items()->createMany($amounts['items']);
            }

            return $this->quotation($locked->fresh());
        });
    }

    public function delete(Quotation $quotation): void
    {
        DB::transaction(function () use ($quotation): void {
            $locked = Quotation::query()->lockForUpdate()->findOrFail($quotation->id);
            $this->assertDraft($locked);
            $locked->delete();
        });
    }

    /** @throws \Throwable */
    public function send(Quotation $quotation): array
    {
        return DB::transaction(function () use ($quotation): array {
            $locked = Quotation::query()->with(['customer', 'items.vehicleType'])->lockForUpdate()->findOrFail($quotation->id);
            $this->assertDraft($locked);
            if (blank($locked->customer->email)) {
                abort(422, 'Khách hàng cần có email trước khi gửi báo giá.');
            }
            $plainToken = Str::random(80);
            $expiresAt = $locked->valid_until?->copy()->endOfDay() ?? now()->addDays(7);
            if ($expiresAt->isPast()) {
                abort(422, 'Báo giá đã hết hiệu lực và không thể gửi.');
            }
            $locked->forceFill(['status' => QuotationStatusEnum::SENT])->save();
            QuotationResponseToken::create([
                'quotation_id' => $locked->id,
                'token_hash' => hash('sha256', $plainToken),
                'expires_at' => $expiresAt,
            ]);
            if ($locked->rental_request_id !== null) {
                $request = RentalRequest::query()->lockForUpdate()->find($locked->rental_request_id);
                if ($request !== null && $request->status === RentalRequestStatusEnum::NEW) {
                    $request->forceFill(['status' => RentalRequestStatusEnum::QUOTED])->save();
                }
            }
            $frontendUrl = (string) config('rental.frontend_quotation_response_url');
            if (blank($frontendUrl)) {
                abort(500, 'FRONTEND_QUOTATION_RESPONSE_URL chưa được cấu hình.');
            }
            $responseUrl = rtrim($frontendUrl, '?/').'?token='.urlencode($plainToken);
            Mail::to($locked->customer->email)->queue((new QuotationSentMail($locked, $responseUrl))->afterCommit());

            return $this->quotation($locked->fresh());
        });
    }

    public function expire(Quotation $quotation): array
    {
        return DB::transaction(function () use ($quotation): array {
            $locked = Quotation::query()->lockForUpdate()->findOrFail($quotation->id);
            if ($locked->status !== QuotationStatusEnum::SENT) {
                abort(409, 'Chỉ báo giá đã gửi mới có thể hết hạn.');
            }
            $locked->forceFill(['status' => QuotationStatusEnum::EXPIRED])->save();

            return $this->quotation($locked->fresh());
        });
    }

    public function responseSummary(string $token): array
    {
        return $this->publicQuotation($this->findUsableToken($token)->quotation);
    }

    public function acceptResponse(string $token): array
    {
        return $this->respond($token, true, null);
    }

    public function rejectResponse(string $token, ?string $note): array
    {
        return $this->respond($token, false, $note);
    }

    private function respond(string $plainToken, bool $accepted, ?string $note): array
    {
        return DB::transaction(function () use ($plainToken, $accepted, $note): array {
            $token = QuotationResponseToken::query()->with('quotation')->lockForUpdate()
                ->where('token_hash', hash('sha256', $plainToken))->first();
            if ($token === null || $token->used_at !== null || $token->expires_at->isPast() || $token->quotation->status !== QuotationStatusEnum::SENT) {
                abort(410, 'Liên kết phản hồi không còn hiệu lực.');
            }
            $quotation = Quotation::query()->lockForUpdate()->findOrFail($token->quotation_id);
            $quotation->forceFill([
                'status' => $accepted ? QuotationStatusEnum::APPROVED : QuotationStatusEnum::REJECTED,
                'approved_at' => $accepted ? now() : null,
                'customer_responded_at' => now(),
                'customer_response_note' => $note,
            ])->save();
            $token->forceFill(['used_at' => now()])->save();
            if ($quotation->rental_request_id !== null) {
                $request = RentalRequest::query()->lockForUpdate()->find($quotation->rental_request_id);
                if ($request !== null && $request->status === RentalRequestStatusEnum::QUOTED) {
                    $request->forceFill(['status' => $accepted ? RentalRequestStatusEnum::ACCEPTED : RentalRequestStatusEnum::REJECTED])->save();
                }
            }

            return $this->publicQuotation($quotation->fresh());
        });
    }

    private function findUsableToken(string $plainToken): QuotationResponseToken
    {
        $token = QuotationResponseToken::query()->with('quotation.customer', 'quotation.items.vehicleType')
            ->where('token_hash', hash('sha256', $plainToken))->first();
        if ($token === null || $token->used_at !== null || $token->expires_at->isPast() || $token->quotation->status !== QuotationStatusEnum::SENT) {
            abort(410, 'Liên kết phản hồi không còn hiệu lực.');
        }

        return $token;
    }

    private function quotation(Quotation $quotation): array
    {
        $quotation->loadMissing(['customer:id,name,email,contact_name', 'rentalRequest:id,request_no,status', 'items.vehicleType:id,name', 'items.route:id,name']);

        return [
            'id' => $quotation->id, 'quotation_no' => $quotation->quotation_no, 'rental_request_id' => $quotation->rental_request_id,
            'rental_request_no' => $quotation->rentalRequest?->request_no, 'customer_id' => $quotation->customer_id,
            'customer_name' => $quotation->customer?->name, 'quotation_date' => $quotation->quotation_date?->toDateString(),
            'valid_until' => $quotation->valid_until?->toDateString(), 'subtotal' => $quotation->subtotal,
            'discount_amount' => $quotation->discount_amount, 'total_amount' => $quotation->total_amount,
            'payment_terms' => $quotation->payment_terms, 'status' => $quotation->status?->value,
            'approved_at' => $quotation->approved_at?->toISOString(), 'customer_responded_at' => $quotation->customer_responded_at?->toISOString(),
            'customer_response_note' => $quotation->customer_response_note,
            'items' => $quotation->items->map(static fn ($item): array => [
                'id' => $item->id, 'route_id' => $item->route_id, 'route_name' => $item->route?->name,
                'vehicle_type_id' => $item->vehicle_type_id, 'vehicle_type_name' => $item->vehicleType?->name,
                'description' => $item->description, 'quantity' => $item->quantity, 'unit_price' => $item->unit_price, 'amount' => $item->amount,
            ])->values()->all(),
        ];
    }

    private function publicQuotation(Quotation $quotation): array
    {
        $data = $this->quotation($quotation);

        return array_intersect_key($data, array_flip(['quotation_no', 'customer_name', 'quotation_date', 'valid_until', 'subtotal', 'discount_amount', 'total_amount', 'payment_terms', 'status', 'items']));
    }

    /** @param list<QuotationItemData> $items */
    private function ensureCustomerAndRelations(int $customerId, ?int $requestId, array $items): void
    {
        if (! Customer::query()->whereKey($customerId)->where('is_active', true)->exists()) {
            abort(422, 'Khách hàng không hợp lệ hoặc đã ngừng hoạt động.');
        }
        if ($requestId !== null) {
            $request = RentalRequest::query()->findOrFail($requestId);
            if ($request->customer_id !== $customerId || ! in_array($request->status, [RentalRequestStatusEnum::NEW, RentalRequestStatusEnum::QUOTED], true)) {
                abort(422, 'Yêu cầu thuê xe không hợp lệ để lập báo giá.');
            }
        }
        $vehicleIds = collect($items)->pluck('vehicleTypeId')->unique();
        if (VehicleType::query()->whereIn('id', $vehicleIds)->where('is_active', true)->count() !== $vehicleIds->count()) {
            abort(422, 'Loại xe không hợp lệ hoặc đã ngừng hoạt động.');
        }
        $routeIds = collect($items)->pluck('routeId')->filter()->unique();
        if (Route::query()->whereIn('id', $routeIds)->where('is_active', true)->count() !== $routeIds->count()) {
            abort(422, 'Tuyến không hợp lệ hoặc đã ngừng hoạt động.');
        }
    }

    /** @param list<QuotationItemData> $items @return array{subtotal:string,discount:string,total:string,items:list<array<string, mixed>>} */
    private function amounts(array $items, string $discount): array
    {
        $subtotal = '0.00';
        $result = [];
        foreach ($items as $item) {
            $amount = bcmul($item->unitPrice, (string) $item->quantity, 2);
            $subtotal = bcadd($subtotal, $amount, 2);
            $result[] = ['route_id' => $item->routeId, 'vehicle_type_id' => $item->vehicleTypeId, 'description' => $item->description, 'quantity' => $item->quantity, 'unit_price' => $item->unitPrice, 'amount' => $amount];
        }
        if (bccomp($discount, $subtotal, 2) === 1) {
            abort(422, 'Chiết khấu không được lớn hơn tạm tính.');
        }

        return ['subtotal' => $subtotal, 'discount' => $discount, 'total' => bcsub($subtotal, $discount, 2), 'items' => $result];
    }

    private function assertDraft(Quotation $quotation): void
    {
        if ($quotation->status !== QuotationStatusEnum::DRAFT) {
            abort(409, 'Chỉ báo giá nháp mới được chỉnh sửa.');
        }
    }

    private function nextQuotationNo(int $year): string
    {
        $prefix = 'BG'.$year;
        $highest = Quotation::query()->where('quotation_no', 'like', $prefix.'%')->lockForUpdate()->pluck('quotation_no')
            ->reduce(static fn (int $carry, string $number): int => preg_match('/^'.preg_quote($prefix, '/').'(\\d{4,})$/', $number, $matches) === 1 ? max($carry, (int) $matches[1]) : $carry, 0);

        return $prefix.str_pad((string) ($highest + 1), 4, '0', STR_PAD_LEFT);
    }
}
