<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('instagram:sync')->hourly()->withoutOverlapping()->onOneServer();
Schedule::command('sponsorships:send-reminders')->dailyAt('08:00')->withoutOverlapping()->onOneServer();
Schedule::command('model:prune')->daily()->onOneServer();
Schedule::command('instagram:refresh-token')->weekly()->withoutOverlapping()->onOneServer();
