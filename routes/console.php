<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly database + file backup, with old/incomplete backups pruned afterwards.
Schedule::command('backup:run')->daily()->at('02:00');
Schedule::command('backup:clean')->daily()->at('01:30');

// Automated executive reporting: yesterday's figures every morning,
// plus a rolled-up 7-day summary every Monday.
Schedule::command('report:send --range=daily')->dailyAt('06:00');
Schedule::command('report:send --range=weekly')->weeklyOn(1, '06:15');

// Snapshot last month's revenue on the 1st, right after Payment History's
// "This Month" figures reset — so the totals live on in the Monthly Archive.
Schedule::command('app:archive-monthly-revenue')->monthlyOn(1, '02:30');
