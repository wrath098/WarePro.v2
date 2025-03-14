<?php

use App\Console\Commands\NewYearLogic;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('year:change', function () {
    (new NewYearLogic)->handle();
});
