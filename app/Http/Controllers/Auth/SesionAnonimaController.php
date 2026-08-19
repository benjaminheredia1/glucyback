<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

/**
 * Alta de un paciente anonimo.
 *
 * Hasta la entrega de estudios el paciente no se registra. Esta ruta le da una
 * identidad temporal (User + Paciente sin correo) y un token Sanctum con el
 * que usa la API como cualquier paciente. Al registrarse por Auth0 mandando
 * ese mismo Bearer, Auth0SessionController rellena la misma fila: nada se
 * mueve de tabla.
 *
 * La credencial es el token, nunca el id: el id es secuencial y con el
 * cualquiera leeria estudios ajenos.
 */
class SesionAnonimaController extends Controller
{
    #[OA\Post(
        path: '/auth/anonimo',
        tags: ['Auth'],
        summary: 'Crear paciente anonimo y obtener token',
        description: 'Identidad temporal (usuario + paciente sin correo) para usar la API antes de registrarse. El token es la unica credencial; se reclama luego en POST /auth/auth0. Limite: 5 por minuto.',
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(
            properties: [new OA\Property(property: 'dispositivo', type: 'string', maxLength: 100)],
        )),
        responses: [
            new OA\Response(response: 201, description: 'Paciente anonimo creado', content: new OA\JsonContent(ref: '#/components/schemas/SesionIniciada')),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'dispositivo' => ['sometimes', 'string', 'max:100'],
        ]);

        $usuario = DB::transaction(function () use ($request) {
            $usuario = User::create([
                'name' => User::NOMBRE_TEMPORAL,
                'email' => null,
                'auth0Sub' => null,
                'password' => null,
                'rol' => User::ROL_PACIENTE,
            ]);

            // Mismo paciente minimo que crea el alta por Auth0: sin esta fila
            // Alcance::pacienteId() no lo encuentra y no podria subir estudios.
            Paciente::create(['usuarioId' => $usuario->id]);

            AuditLog::create([
                'usuarioId' => $usuario->id,
                'entidad' => class_basename($usuario),
                'entidadId' => $usuario->getKey(),
                'accion' => 'crear-anonimo',
                'antes' => null,
                'despues' => $usuario->toArray(),
                'ip' => $request->ip(),
            ]);

            return $usuario;
        });

        return response()->json([
            'token' => $usuario->createToken($datos['dispositivo'] ?? 'api')->plainTextToken,
            'usuario' => $usuario->load(['doctor.clinica', 'paciente']),
        ], 201);
    }
}
