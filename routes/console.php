<?php

use App\Jobs\SyncActiveHotspotUsersJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('mikrotik:check-status')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::job((new SyncActiveHotspotUsersJob())->onQueue('active-user-sync'))
    ->everyTwoMinutes()
    ->withoutOverlapping();
