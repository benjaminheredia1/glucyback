<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Sesion Sanctum en rutas publicas.
 *
 * Las rutas del onboarding (alta por Auth0, precalificacion) no exigen sesion,
 * pero si el paciente anonimo manda su Bearer tienen que reconocerlo. Un Bearer
 * presente pero invalido no se ignora en silencio: si la app cree que reclama
 * la cuenta y no la reclama, el paciente pierde sus estudios sin enterarse.
 */
class SesionOpcional
{
    public static function usuario(Request $request): ?User
    {
        // El guard de Sanctum resuelve el Bearer aunque la ruta no lleve el
        // middleware auth:sanctum; tambien cubre actingAs() en tests.
        $usuario = $request->user('sanctum');

        if ($usuario instanceof User) {
            return $usuario;
        }

        abort_if($request->bearerToken() !== null, 401, 'El token de la sesion no es valido.');

        return null;
    }
}
