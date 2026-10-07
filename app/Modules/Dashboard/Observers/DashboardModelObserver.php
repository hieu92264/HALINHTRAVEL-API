<?php

namespace App\Modules\Dashboard\Observers;

use App\Modules\Dashboard\Services\DashboardRealtimeNotifier;
use Illuminate\Database\Eloquent\Model;

class DashboardModelObserver
{
    public function created(Model $model): void
    {
        $this->notify($model);
    }

    public function updated(Model $model): void
    {
        $this->notify($model);
    }

    public function deleted(Model $model): void
    {
        $this->notify($model);
    }

    private function notify(Model $model): void
    {
        app(DashboardRealtimeNotifier::class)->notify($model);
    }
}
