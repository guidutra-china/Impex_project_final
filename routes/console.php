<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule currency rate updates
// Uncomment and customize the schedule below:

// Update currency rates every day at 2 AM
// Schedule::command('currency:update-rates')
//     ->daily()
//     ->at('02:00');

// Generate recurring financial transactions every day at 3 AM
Schedule::command('finance:generate-recurring')
    ->daily()
    ->at('03:00');

// Other scheduling options:
// ->hourly()                           // Every hour
// ->everyTwoHours()                    // Every 2 hours
// ->everySixHours()                    // Every 6 hours
// ->daily()->at('09:00')               // Daily at 9 AM
// ->weekdays()->at('09:00')            // Weekdays at 9 AM
// ->weekly()->mondays()->at('08:00')   // Every Monday at 8 AM
// ->monthly()                          // First day of every month
