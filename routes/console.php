<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Schedule Church Saturday Service Reminders & Invitations
 * Runs automatically every Saturday morning at 07:00 AM
 */
\Illuminate\Support\Facades\Schedule::command('sms:saturday-reminder')
    ->saturdays()
    ->at('07:00')
    ->withoutOverlapping()
    ->runInBackground();
