<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Login local del panel de gestion (glucyfront): correo y contrasena.
 *
 * Devuelve el mismo par {token, usuario} que POST /auth/auth0, asi el panel
 * consume la API igual que antes. Solo entran cuentas que operan el panel
 * (admin y doctor); un paciente usa la app movil y Auth0.
 */
class PanelSessionController extends Controller
{
    /** @var array<string> */
    private const ROLES_PANEL = [User::ROL_ADMIN, User::ROL_DOCTOR];

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'dispositivo' => ['sometimes', 'string', 'max:100'],
        ]);

        // Mismo mensaje para "no existe", "sin contrasena local" y "contrasena
        // mal": no se revela cual de las tres fallo.
        $usuario = User::where('email', trim(mb_strtolower($datos['email'])))->first();

        abort_if(
            $usuario === null
                || $usuario->password === null
                || ! Hash::check($datos['password'], $usuario->password),
            401,
            'Credenciales invalidas.'
        );

        abort_unless(
            in_array($usuario->rol, self::ROLES_PANEL, true),
            403,
            'Esta cuenta no tiene acceso al panel de gestion.'
        );

        $nombreToken = $datos['dispositivo'] ?? 'glucyfront';

        // Un token por dispositivo: reentrar desde el mismo equipo no acumula.
        $usuario->tokens()->where('name', $nombreToken)->delete();

        return response()->json([
            'token' => $usuario->createToken($nombreToken)->plainTextToken,
            'usuario' => $usuario->load(['doctor.clinica', 'paciente']),
        ]);
    }
}
