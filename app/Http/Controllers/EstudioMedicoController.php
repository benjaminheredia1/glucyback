<?php

namespace App\Http\Controllers;

use App\Models\Archivo;
use App\Models\EstudioMedico;
use App\Models\User;
use App\Support\Alcance;
use App\Support\GeneracionPlanClinicoIa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class EstudioMedicoController extends BaseCrudController
{
    protected string $modelo = EstudioMedico::class;

    protected array $rolesEscritura = [User::ROL_ADMIN, User::ROL_DOCTOR, User::ROL_PACIENTE];

    // paciente.usuario permite que el portal medico muestre de quien es cada
    // estudio sin una peticion extra; el alcance ya acota a la clinica.
    protected array $with = ['tipoEstudio', 'archivo', 'paciente.usuario'];

    protected ?string $columnaPaciente = 'pacienteId';

    protected bool $forzarPacientePropio = true;

    protected array $filtrables = ['pacienteId', 'tipoEstudioId', 'estado', 'origen'];

    protected array $ordenables = ['id', 'fecha', 'created_at'];

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'tipoEstudioId' => [$req, 'exists:tipo_estudios,id'],
            'pacienteId' => [$req, 'exists:pacientes,id'],
            'archivoId' => ['nullable', 'exists:archivos,id'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'fecha' => [$req, 'date'],
            'valor' => ['nullable', 'numeric'],
            'unidad' => ['nullable', 'string', 'max:50'],
            'origen' => ['sometimes', 'in:carga,laboratorio'],
        ];
    }

    /**
     * La app registra el estudio despues de POST /archivos/subir. Si la IA ya
     * aprobo ese tipo con ese mismo archivo, no se abre otra revision: se
     * devuelve el estudio aprobado tal cual. La unica aprobacion humana del
     * flujo es la del tratamiento (aceptadoDoctor).
     */
    public function crear(Request $request): JsonResponse
    {
        if ($request->filled('archivoId') && $request->filled('tipoEstudioId')) {
            $aprobado = $this->consulta($request)
                ->where('tipoEstudioId', $request->input('tipoEstudioId'))
                ->where('archivoId', $request->input('archivoId'))
                ->where('estado', 'aprobado')
                ->first();

            if ($aprobado !== null) {
                return response()->json($aprobado, 200);
            }
        }

        return parent::crear($request);
    }

    /**
     * Un estudio rechazado se vuelve a subir como intento siguiente. El estado y
     * el motivo los fija el doctor en `validar()`, nunca el paciente.
     */
    protected function antesDeCrear(Request $request, array $datos): array
    {
        // El archivoId llega del cliente: hay que comprobar que sea suyo y no de
        // otro paciente. `exists:` solo garantiza que la fila existe.
        Archivo::exigirVisible($datos['archivoId'] ?? null, $request->user());

        $datos['estado'] = 'pendiente';
        $datos['intento'] = EstudioMedico::withTrashed()
            ->where('pacienteId', $datos['pacienteId'])
            ->where('tipoEstudioId', $datos['tipoEstudioId'])
            ->count() + 1;

        return $datos;
    }

    protected function antesDeActualizar(Request $request, Model $registro, array $datos): array
    {
        Archivo::exigirVisible($datos['archivoId'] ?? null, $request->user());

        // El veredicto lo fija el doctor en `validarEstudio()`, nunca un PATCH.
        unset($datos['estado'], $datos['motivoRechazo'], $datos['validadoPor'], $datos['validadoEn'], $datos['intento']);

        return $datos;
    }

    #[OA\Post(
        path: '/estudios-medicos/{id}/validar',
        tags: ['Estudio Medico'],
        summary: 'Validar estudio medico',
        description: 'Cambia el estado de revision. Si se rechaza, motivoRechazo es obligatorio.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['estado'], properties: [new OA\Property(property: 'estado', type: 'string', enum: ['en_revision', 'aprobado', 'rechazado']), new OA\Property(property: 'motivoRechazo', type: 'string', maxLength: 255, nullable: true)])),
        responses: [
            new OA\Response(response: 200, description: 'Estudio validado', content: new OA\JsonContent(ref: '#/components/schemas/EstudioMedico')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function validarEstudio(Request $request, int $id): JsonResponse
    {
        $this->autorizar($request);

        $estudio = $this->consulta($request)->findOrFail($id);

        $datos = $request->validate([
            'estado' => ['required', 'in:en_revision,aprobado,rechazado'],
            'motivoRechazo' => ['required_if:estado,rechazado', 'nullable', 'string', 'max:255'],
        ]);

        $doctor = Alcance::doctor($request->user());

        $antes = $estudio->toArray();

        $estudio->update([
            'estado' => $datos['estado'],
            'motivoRechazo' => $datos['estado'] === 'rechazado' ? $datos['motivoRechazo'] : null,
            'validadoPor' => $doctor?->id,
            'validadoEn' => now(),
        ]);

        $this->auditar($request, 'validar', $estudio, $antes, $estudio->toArray());

        if ($estudio->estado === 'aprobado' && $estudio->paciente !== null) {
            // La aprobacion manual tambien puede completar los obligatorios.
            app(GeneracionPlanClinicoIa::class)->generarSiCorresponde($estudio->paciente);
        }

        return response()->json($estudio->fresh($this->with));
    }

    private function autorizar(Request $request): void
    {
        $usuario = $request->user();

        abort_unless(
            $usuario !== null && ($usuario->esAdmin() || $usuario->esDoctor()),
            403,
            'Solo un doctor puede validar un estudio.'
        );
    }
}
