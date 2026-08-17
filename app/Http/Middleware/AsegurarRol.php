<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AsegurarRol
{
    /**
     * Uso: ->middleware('rol:admin,doctor')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        if ($usuario === null) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        if ($roles !== [] && ! in_array($usuario->rol, $roles, true)) {
            return response()->json(['message' => 'No autorizado para este recurso.'], 403);
        }

        return $next($request);
    }
}
