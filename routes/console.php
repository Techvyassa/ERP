<?php

use App\Jobs\CheckTrialExpiration;
use App\Jobs\CleanupExpiredTokens;
use App\Jobs\EnforceGracePeriod;
use App\Jobs\ProcessSubscriptionRenewal;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Subscription lifecycle management scheduled jobs
// Requirements: 6.3, 6.6, 6.10

// Check for expired trial subscriptions daily at 1:00 AM
Schedule::job(new CheckTrialExpiration())->dailyAt('01:00');

// Process subscription renewals daily at 2:00 AM
Schedule::job(new ProcessSubscriptionRenewal())->dailyAt('02:00');

// Enforce grace period for past due subscriptions daily at 3:00 AM
Schedule::job(new EnforceGracePeriod())->dailyAt('03:00');

// Cleanup expired refresh tokens daily at 4:00 AM
Schedule::job(new CleanupExpiredTokens())->dailyAt('04:00');
