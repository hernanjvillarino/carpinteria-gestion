<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Requiere en el servidor: * * * * * php artisan schedule:run
Schedule::command('personal:avisar-vencimientos')->dailyAt('08:00');
