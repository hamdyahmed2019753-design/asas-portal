<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Internal daily refresh of payout statuses (scheduled -> due). No external sending.
Schedule::command('payouts:refresh')->dailyAt('00:10');
