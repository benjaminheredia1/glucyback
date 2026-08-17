<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// La ruta de alta anonima es publica: sin esta purga users crece sin limite.
Schedule::command('usuarios:purgar-anonimos')->daily();
