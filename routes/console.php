<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

app(Schedule::class)
    ->command('rental:expire-quotations')
    ->dailyAt('00:05')
    ->timezone('Asia/Ho_Chi_Minh');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
