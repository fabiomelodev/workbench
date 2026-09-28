<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Assinaturas: garante a próxima mensalidade de cada assinatura ativa.
Schedule::command('transactions:generate-subscriptions')->dailyAt('06:00');
