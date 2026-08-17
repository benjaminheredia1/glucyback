<?php

namespace App\Http\Controllers;

use App\Models\Precalificacion;
use App\Models\PreguntaPrecalificacion;
use App\Models\User;
use App\Support\Alcance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrecalificacionController extends BaseCrudController
{
    protected string $modelo = Precalificacion::class;

    protected array $rolesEscritura = [User::ROL_ADMIN, User::ROL_DOCTOR, User::ROL_PACIENTE];

    protected array $with = ['respuestas.pregunta'];

    protected ?string $columnaPaciente = 'pacienteId';

    protected bool $forzarPacientePropio = true;

    protected array $filtrables = ['resultado', 'pacienteId'];

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'pacienteId' => ['nullable', 'exists:pacientes,id'],
            'leadEmail' => ['nullable', 'email', 'max:255'],
            'resultado' => [$req, 'in:apto,no_apto,urgente'],
            'motivo' => ['nullable', 'string', 'max:255'],
            'versionCuestionario' => ['sometimes', 'integer', 'min:1'],
            'respondidoEn' => ['sometimes', 'date'],
        ];
    }

    protected function antesDeCrear(Request $request, array $datos): array
    {
        $datos['respondidoEn'] ??= now();

        return $datos;
    }

    /**
     * Evalua el cuestionario completo y guarda veredicto + respuestas.
     *
     * Es el unico camino que garantiza que `resultado` concuerde con la evidencia:
     * el CRUD plano permitiria guardar un veredicto sin respuestas que lo sostengan.
     */
    public function evaluar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'pacienteId' => ['nullable', 'exists:pacientes,id'],
            'leadEmail' => ['nullable', 'email', 'max:255'],
            'respuestas' => ['required', 'array', 'min:1'],
            'respuestas.*.preguntaId' => ['required', 'exists:preguntas_precalificacion,id'],
            'respuestas.*.respuesta' => ['required', 'in:si,no'],
        ]);

        $preguntas = PreguntaPrecalificacion::whereIn(
            'id',
            collect($datos['respuestas'])->pluck('preguntaId')
        )->get()->keyBy('id');

        $activas = PreguntaPrecalificacion::where('activa', true)->count();

        abort_if(
            count($datos['respuestas']) < $activas,
            422,
            "El filtro clinico exige responder las {$activas} preguntas activas."
        );

        $resultado = 'apto';
        $motivo = null;
        $version = $preguntas->max('version') ?? 1;

        foreach ($datos['respuestas'] as $fila) {
            $pregunta = $preguntas[$fila['preguntaId']];

            if ($fila['respuesta'] !== $pregunta->respuestaAlarma) {
                continue;
            }

            // Una alarma urgente gana sobre cualquier otra: corta el proceso.
            if ($pregunta->esUrgente) {
                $resultado = 'urgente';
                $motivo = $pregunta->motivo;
                break;
            }

            if ($resultado === 'apto') {
                $resultado = 'no_apto';
                $motivo = $pregunta->motivo;
            }
        }

        $precalificacion = DB::transaction(function () use ($datos, $resultado, $motivo, $version) {
            $precalificacion = Precalificacion::create([
                'pacienteId' => $datos['pacienteId'] ?? null,
                'leadEmail' => $datos['leadEmail'] ?? null,
                'resultado' => $resultado,
                'motivo' => $motivo,
                'versionCuestionario' => $version,
                'respondidoEn' => now(),
            ]);

            foreach ($datos['respuestas'] as $fila) {
                $precalificacion->respuestas()->create([
                    'preguntaId' => $fila['preguntaId'],
                    'respuesta' => $fila['respuesta'],
                ]);
            }

            return $precalificacion;
        });

        return response()->json($precalificacion->load('respuestas.pregunta'), 201);
    }

    /**
     * Ata una precalificacion anonima al paciente que acaba de crear su cuenta.
     *
     * No pasa por `consulta()`: el alcance filtra por `pacienteId`, que en una
     * precalificacion anonima todavia es nulo, asi que la ocultaria. La
     * credencial aqui es el `leadEmail`, que tiene que coincidir con el correo
     * del usuario autenticado.
     */
    public function vincular(Request $request, int $id): JsonResponse
    {
        $this->autorizarEscritura($request);

        $usuario = $request->user();

        $pacienteId = Alcance::pacienteId($usuario);

        abort_if($pacienteId === null, 422, 'El usuario no tiene perfil de paciente.');

        $precalificacion = Precalificacion::findOrFail($id);

        abort_unless(
            $precalificacion->pacienteId === null,
            409,
            'La precalificacion ya esta vinculada.'
        );

        // trim() porque leadEmail puede llegar sembrado fuera de la ruta publica
        // (que ya normaliza via TrimStrings); strcasecmp ya cubre mayusculas.
        abort_unless(
            $precalificacion->leadEmail !== null
                && strcasecmp(trim($precalificacion->leadEmail), trim($usuario->email)) === 0,
            403,
            'La precalificacion no corresponde a este usuario.'
        );

        $antes = $precalificacion->toArray();

        $precalificacion->update(['pacienteId' => $pacienteId]);

        $this->auditar($request, 'vincular', $precalificacion, $antes, $precalificacion->toArray());

        return response()->json($precalificacion->load('respuestas.pregunta'));
    }
}
