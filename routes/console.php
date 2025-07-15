<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('app:check-midtrans-payment-status')
    ->timezone('Asia/Jakarta')
    ->dailyAt('01:00');
