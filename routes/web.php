<?php

use Illuminate\Support\Facades\Route;

/**
 * La superficie de la aplicacion es la API: el panel web y las dos apps moviles
 * consumen `routes/api.php`. Las rutas de auth vivian tambien aca y duplicaban
 * los nombres (`login`, `register`, ...) de las de la API.
 */
Route::get('/', fn () => ['Laravel' => app()->version()]);
