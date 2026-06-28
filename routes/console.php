<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Lembrete diário de prospecção (atrasados + para hoje), em dias úteis pela manhã.
Schedule::command('prospects:daily-reminder')
    ->weekdays()
    ->dailyAt('08:00');
