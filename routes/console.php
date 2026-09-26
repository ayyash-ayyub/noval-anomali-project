<?php

use App\Jobs\SyncActiveHotspotUsersJob;
use App\Jobs\SyncVoucherLifecycleJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('mikrotik:check-status')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::job((new SyncActiveHotspotUsersJob)->onQueue('active-user-sync'))
    ->everyTwoMinutes()
    ->withoutOverlapping();

Schedule::job((new SyncVoucherLifecycleJob)->onQueue('active-user-sync'))
    ->everyFiveMinutes()
    ->withoutOverlapping();
