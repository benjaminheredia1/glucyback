<?php

namespace App\Http\Controllers;

use App\Models\Caso;
use App\Models\User;
use App\Support\Alcance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Bandeja del doctor. `abiertoEn` / `asignadoEn` / `cerradoEn` son la fuente del
 * SLA que el panel del admin grafica por clinica.
 */
class CasoController extends BaseCrudController
{
    protected string $modelo = Caso::class;

    protected array $rolesLectura = [User::ROL_ADMIN, User::ROL_DOCTOR];

    protected array $rolesEscritura = [User::ROL_ADMIN, User::ROL_DOCTOR];

    protected array $with = ['paciente.usuario', 'doctor.usuario', 'ciclo'];

    protected ?string $columnaPaciente = 'pacienteId';

    protected array $filtrables = ['pacienteId', 'doctorId', 'tipo', 'urgencia', 'estado', 'cicloId'];

    protected array $ordenables = ['id', 'abiertoEn', 'urgencia', 'cerradoEn'];

    protected string $ordenPorDefecto = 'abiertoEn';

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'pacienteId' => [$req, 'exists:pacientes,id'],
            'doctorId' => ['nullable', 'exists:doctores,id'],
            'cicloId' => ['nullable', 'exists:ciclos,id'],
            'tipo' => [$req, 'in:ingreso,ajuste_ciclo,revision_15d,alerta'],
            'urgencia' => ['sometimes', 'in:urgente,pendiente,estable'],
            'titulo' => [$req, 'string', 'max:255'],
            'nota' => ['nullable', 'string'],
        ];
    }

    protected function antesDeCrear(Request $request, array $datos): array
    {
        $datos['abiertoEn'] ??= now();
        $datos['estado'] = 'abierto';

        if (! empty($datos['doctorId'])) {
            $datos['asignadoEn'] = now();
            $datos['estado'] = 'en_proceso';
        }

        return $datos;
    }

    /** El estado se mueve por acciones explicitas, no por PATCH libre. */
    protected function antesDeActualizar(Request $request, Model $registro, array $datos): array
    {
        unset($datos['estado'], $datos['abiertoEn'], $datos['asignadoEn'], $datos['cerradoEn']);

        return $datos;
    }

    #[OA\Post(
        path: '/casos/{id}/asignar',
        tags: ['Caso'],
        summary: 'Asignar caso a un doctor',
        description: 'Si no se manda doctorId se asigna al doctor de la sesion. Falla si el caso esta cerrado.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [new OA\Property(property: 'doctorId', type: 'integer', nullable: true, description: 'Por defecto, el doctor autenticado')])),
        responses: [
            new OA\Response(response: 200, description: 'Caso asignado', content: new OA\JsonContent(ref: '#/components/schemas/Caso')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflicto'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function asignar(Request $request, int $id): JsonResponse
    {
        $this->autorizarEscritura($request);

        $caso = $this->consulta($request)->findOrFail($id);

        $datos = $request->validate([
            'doctorId' => ['nullable', 'exists:doctores,id'],
        ]);

        // Un doctor sin doctorId explicito se autoasigna el caso.
        $doctorId = $datos['doctorId'] ?? Alcance::doctor($request->user())?->id;

        abort_if($doctorId === null, 422, 'No se pudo determinar el doctor a asignar.');
        abort_if($caso->estado === 'cerrado', 409, 'El caso ya esta cerrado.');

        $antes = $caso->toArray();

        $caso->update([
            'doctorId' => $doctorId,
            'asignadoEn' => now(),
            'estado' => 'en_proceso',
        ]);

        $this->auditar($request, 'asignar', $caso, $antes, $caso->toArray());

        return response()->json($caso->fresh($this->with));
    }

    #[OA\Post(
        path: '/casos/{id}/cerrar',
        tags: ['Caso'],
        summary: 'Cerrar caso',
        description: 'Falla si ya estaba cerrado.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Caso cerrado', content: new OA\JsonContent(ref: '#/components/schemas/Caso')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflicto'),
        ],
    )]
    public function cerrar(Request $request, int $id): JsonResponse
    {
        $this->autorizarEscritura($request);

        $caso = $this->consulta($request)->findOrFail($id);

        abort_if($caso->estado === 'cerrado', 409, 'El caso ya esta cerrado.');

        $antes = $caso->toArray();

        $caso->update([
            'estado' => 'cerrado',
            'cerradoEn' => now(),
        ]);

        $this->auditar($request, 'cerrar', $caso, $antes, $caso->toArray());

        return response()->json($caso->fresh($this->with));
    }
}
