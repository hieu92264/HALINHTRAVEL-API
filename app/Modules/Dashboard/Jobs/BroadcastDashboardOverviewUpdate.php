<?php

namespace App\Modules\Dashboard\Jobs;

use App\Modules\Dashboard\Events\DashboardOverviewChanged;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class BroadcastDashboardOverviewUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        /** @var list<string> $sections */
        $sections = Cache::pull('dashboard:realtime:sections', []);
        Cache::forget('dashboard:realtime:queued');
        if ($sections === []) {
            return;
        }

        Cache::increment('dashboard:overview-version');
        DashboardOverviewChanged::dispatch($sections, now()->toISOString());
    }
}
