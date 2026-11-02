<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

app(Schedule::class)
    ->command('worker-vehicles:maintain-window')
    ->dailyAt('00:10')
    ->timezone('Asia/Ho_Chi_Minh');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
