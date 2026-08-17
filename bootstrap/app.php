<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'rol' => \App\Http\Middleware\AsegurarRol::class,
        ]);

        // En produccion Caddy termina TLS y nginx reenvia por FastCGI: sin
        // esto Laravel cree que la peticion es http y genera URLs/cookies mal.
        // Toda la cadena de proxies es nuestra (misma red de docker).
        $middleware->trustProxies(at: '*');

        // La app es solo API: no existe pantalla de login que redirigir. Sin
        // esto, un cliente que no manda "Accept: application/json" (el
        // navegador, curl a secas) hace que Authenticate intente
        // route('login') -que ya no existe desde que se borro el login local-
        // y explota en 500 en vez de responder 401.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
