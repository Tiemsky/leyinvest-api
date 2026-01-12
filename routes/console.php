<?php

use App\Jobs\ProcessTestJob;
use Illuminate\Foundation\Inspiring;
use App\Jobs\SyncBrvmDataToDatabaseJob;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Vérifier et marquer les souscriptions expirées chaque jour à minuit
Schedule::command('subscriptions:expire')->days(7);
Schedule::command('registrations:cleanup')->days(7);

Schedule::job(new SyncBrvmDataToDatabaseJob)->dailyAt('14:00');
Schedule::job(new SyncBrvmDataToDatabaseJob)->dailyAt('00:00');

// Schedule::job(new ProcessTestJob())->everyFiveSeconds();
