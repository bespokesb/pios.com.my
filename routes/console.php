<?php

use App\Console\Commands\GenerateMonthlyBusinessOwnerFees;
use App\Console\Commands\ProcessScheduledWhatsAppBlasts;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(GenerateMonthlyBusinessOwnerFees::class)
    ->monthlyOn(1, '00:30')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command(ProcessScheduledWhatsAppBlasts::class)
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();
