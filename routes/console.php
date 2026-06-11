<?php

use App\Jobs\AggregateDailyStatsJob;
use App\Jobs\SubscriptionLifecycleJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new AggregateDailyStatsJob)->dailyAt('00:30');
Schedule::job(new SubscriptionLifecycleJob)->dailyAt('01:00');
