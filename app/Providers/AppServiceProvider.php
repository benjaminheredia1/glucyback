<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Auth0\VerificadorAuth0;
use App\Support\Auth0\VerificadorAuth0Remoto;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            VerificadorAuth0::class,
            VerificadorAuth0Remoto::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        // sanctum.expiration vence todo token a las 24 h y la app lo renueva
        // en silencio via Auth0. El paciente anonimo no tiene Auth0 con que
        // renovar: su token vale hasta que reclame la cuenta (se revocan todos)
        // o hasta que la purga por inactividad lo de de baja.
        Sanctum::authenticateAccessTokensUsing(function (PersonalAccessToken $token, bool $esValido): bool {
            $usuario = $token->tokenable;

            if ($usuario instanceof User && $usuario->esTemporal()) {
                return $token->expires_at === null || ! $token->expires_at->isPast();
            }

            return $esValido;
        });
    }
}
