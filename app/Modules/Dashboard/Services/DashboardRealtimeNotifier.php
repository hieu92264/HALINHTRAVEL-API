<?php

namespace App\Modules\Dashboard\Services;

use App\Modules\Dashboard\Jobs\BroadcastDashboardOverviewUpdate;
use App\Modules\Finance\Models\Expense;
use App\Modules\Finance\Models\PartnerPayment;
use App\Modules\Finance\Models\Receipt;
use App\Modules\MasterData\Models\Driver;
use App\Modules\VehicleOperations\Models\TouristVehicle;
use App\Modules\VehicleOperations\Models\WorkerVehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class DashboardRealtimeNotifier
{
    public function notify(Model $model): void
    {
        $sections = $this->sectionsFor($model);
        if ($sections === []) {
            return;
        }

        $pending = collect(Cache::get('dashboard:realtime:sections', []))
            ->merge($sections)
            ->unique()
            ->values()
            ->all();
        Cache::put('dashboard:realtime:sections', $pending, now()->addSeconds(3));

        if (Cache::add('dashboard:realtime:queued', true, now()->addSeconds(3))) {
            BroadcastDashboardOverviewUpdate::dispatch()
                ->onQueue('realtime')
                ->delay(now()->addSecond())
                ->afterCommit();
        }
    }

    /** @return list<string> */
    private function sectionsFor(Model $model): array
    {
        return match ($model::class) {
            WorkerVehicle::class, TouristVehicle::class => ['operations', 'alerts', 'fleet'],
            Driver::class => ['fleet', 'alerts'],
            Receipt::class, Expense::class, PartnerPayment::class => ['finance'],
            default => [],
        };
    }
}
