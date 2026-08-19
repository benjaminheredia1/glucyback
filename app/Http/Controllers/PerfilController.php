<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

/**
 * Autoedicion del perfil de la cuenta autenticada.
 *
 * Existe porque UsuarioController y PacienteController reservan la escritura a
 * admin (y doctor): un paciente no puede tocar su propia fila por el CRUD. Este
 * endpoint abre solo los campos inofensivos del perfil; email (es la identidad
 * en Auth0), rol, clinicaId y tipoDiabetes quedan fuera a proposito.
 */
class PerfilController extends Controller
{
    private const CAMPOS_USUARIO = ['name', 'apellidoPaterno', 'apellidoMaterno', 'telefono'];

    private const CAMPOS_PACIENTE = ['fechaNacimiento', 'sexo', 'pesoKg', 'tallaCm'];

    #[OA\Patch(
        path: '/perfil',
        tags: ['Perfil'],
        summary: 'Actualizar el perfil propio',
        description: 'Unico camino de escritura del paciente sobre sus datos personales. Email, rol, clinica y tipo de diabetes no se pueden tocar aqui.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', maxLength: 255),
                new OA\Property(property: 'apellidoPaterno', type: 'string', maxLength: 255, nullable: true),
                new OA\Property(property: 'apellidoMaterno', type: 'string', maxLength: 255, nullable: true),
                new OA\Property(property: 'telefono', type: 'string', maxLength: 50, nullable: true),
                new OA\Property(property: 'fechaNacimiento', type: 'string', format: 'date', nullable: true),
                new OA\Property(property: 'sexo', type: 'string', enum: ['femenino', 'masculino', 'otro'], nullable: true),
                new OA\Property(property: 'pesoKg', type: 'number', minimum: 1, maximum: 400, nullable: true),
                new OA\Property(property: 'tallaCm', type: 'integer', minimum: 30, maximum: 260, nullable: true),
            ],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Usuario actualizado con doctor.clinica y paciente', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function actualizar(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $datos = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'apellidoPaterno' => ['nullable', 'string', 'max:255'],
            'apellidoMaterno' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            // Mismos limites que PacienteController::reglas(), para que el
            // perfil no pueda guardar lo que el CRUD clinico rechazaria.
            'fechaNacimiento' => ['nullable', 'date', 'before_or_equal:today'],
            'sexo' => ['nullable', 'in:femenino,masculino,otro'],
            'pesoKg' => ['nullable', 'numeric', 'between:1,400'],
            'tallaCm' => ['nullable', 'integer', 'between:30,260'],
        ]);

        $delUsuario = array_intersect_key($datos, array_flip(self::CAMPOS_USUARIO));
        $delPaciente = array_intersect_key($datos, array_flip(self::CAMPOS_PACIENTE));

        DB::transaction(function () use ($request, $usuario, $delUsuario, $delPaciente) {
            if ($delUsuario !== []) {
                $antes = $usuario->toArray();
                $usuario->update($delUsuario);
                $this->auditar($request, $usuario, $antes, $usuario->toArray());
            }

            // Los campos clinicos solo aplican a cuentas con fila de paciente;
            // para doctor o admin se ignoran en silencio en vez de fallar.
            $paciente = $usuario->paciente;

            if ($delPaciente !== [] && $paciente !== null) {
                $antes = $paciente->toArray();
                $paciente->update($delPaciente);
                $this->auditar($request, $paciente, $antes, $paciente->toArray());
            }
        });

        return response()->json($usuario->fresh()->load(['doctor.clinica', 'paciente']));
    }

    /** Mismo formato que BaseCrudController::auditar(). */
    private function auditar(Request $request, object $registro, array $antes, array $despues): void
    {
        AuditLog::create([
            'usuarioId' => $request->user()->id,
            'entidad' => class_basename($registro),
            'entidadId' => $registro->getKey(),
            'accion' => 'actualizar',
            'antes' => $antes,
            'despues' => $despues,
            'ip' => $request->ip(),
        ]);
    }
}
