<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
