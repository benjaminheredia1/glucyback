<?php

namespace App\Http\Controllers;

use App\Models\Tratamiento;
use App\Models\User;
use App\Support\Alcance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class TratamientoController extends BaseCrudController
{
    protected string $modelo = Tratamiento::class;

    protected array $rolesEscritura = [User::ROL_ADMIN, User::ROL_DOCTOR];

    protected array $with = ['doctor.usuario', 'caso', 'ciclo', 'medicamentos.medicamento'];

    protected ?string $columnaPaciente = 'pacienteId';

    protected array $filtrables = ['pacienteId', 'doctorId', 'casoId', 'cicloId', 'estado'];

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'pacienteId' => [$req, 'exists:pacientes,id'],
            'doctorId' => ['nullable', 'exists:doctores,id'],
            'casoId' => ['nullable', 'exists:casos,id'],
            'cicloId' => ['nullable', 'exists:ciclos,id'],
            'descripcion' => [$req, 'string', 'max:255'],
            'tratamientoAI' => ['nullable', 'string'],
            'tratamientoDoctor' => ['nullable', 'string'],
            'notaDoctor' => ['nullable', 'string'],
            'estado' => ['sometimes', 'in:borrador,pendiente_firma'],
        ];
    }

    protected function antesDeActualizar(Request $request, Model $registro, array $datos): array
    {
        abort_if(
            $registro->estaFirmado(),
            409,
            'Un tratamiento firmado no se edita: usa POST /tratamientos/{id}/reemplazar.'
        );

        unset($datos['firmadoEn'], $datos['enviadoEn'], $datos['version'], $datos['reemplazaA']);

        return $datos;
    }

    protected function antesDeEliminar(Request $request, Model $registro): void
    {
        abort_if($registro->estaFirmado(), 409, 'Un tratamiento firmado no se puede eliminar.');
    }

    #[OA\Post(
        path: '/tratamientos/{id}/firmar',
        tags: ['Tratamiento'],
        summary: 'Firmar tratamiento',
        description: 'Lo firma el doctor de la sesion. Falla si ya esta firmado o si el doctor no corresponde.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Tratamiento firmado', content: new OA\JsonContent(ref: '#/components/schemas/Tratamiento')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflicto'),
        ],
    )]
    public function firmar(Request $request, int $id): JsonResponse
    {
        $this->autorizarEscritura($request);

        $tratamiento = $this->consulta($request)->findOrFail($id);

        abort_if($tratamiento->estaFirmado(), 409, 'El tratamiento ya esta firmado.');

        $doctor = Alcance::doctor($request->user());

        abort_if(
            $doctor === null && ! $request->user()->esAdmin(),
            422,
            'Solo un doctor con matricula puede firmar.'
        );

        abort_if(
            blank($tratamiento->tratamientoDoctor),
            422,
            'No se puede firmar un tratamiento sin indicaciones del doctor.'
        );

        $antes = $tratamiento->toArray();

        $tratamiento->update([
            'doctorId' => $doctor?->id ?? $tratamiento->doctorId,
            'estado' => 'firmado',
            'firmadoEn' => now(),
        ]);

        $this->auditar($request, 'firmar', $tratamiento, $antes, $tratamiento->toArray());

        return response()->json($tratamiento->fresh($this->with));
    }

    #[OA\Post(
        path: '/tratamientos/{id}/enviar',
        tags: ['Tratamiento'],
        summary: 'Enviar tratamiento al paciente',
        description: 'Solo se envia un tratamiento firmado.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Tratamiento enviado', content: new OA\JsonContent(ref: '#/components/schemas/Tratamiento')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflicto'),
        ],
    )]
    public function enviar(Request $request, int $id): JsonResponse
    {
        $this->autorizarEscritura($request);

        $tratamiento = $this->consulta($request)->findOrFail($id);

        abort_unless($tratamiento->estado === 'firmado', 409, 'Solo se envia un tratamiento firmado.');

        $antes = $tratamiento->toArray();

        $tratamiento->update(['estado' => 'enviado', 'enviadoEn' => now()]);

        $this->auditar($request, 'enviar', $tratamiento, $antes, $tratamiento->toArray());

        return response()->json($tratamiento->fresh($this->with));
    }

    #[OA\Post(
        path: '/tratamientos/{id}/reemplazar',
        tags: ['Tratamiento'],
        summary: 'Reemplazar tratamiento firmado por uno nuevo',
        description: 'Crea un tratamiento nuevo ligado al anterior. Solo sobre uno firmado y no reemplazado aun.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['descripcion'], properties: [new OA\Property(property: 'descripcion', type: 'string', maxLength: 255), new OA\Property(property: 'tratamientoAI', type: 'string', nullable: true), new OA\Property(property: 'tratamientoDoctor', type: 'string', nullable: true), new OA\Property(property: 'notaDoctor', type: 'string', nullable: true), new OA\Property(property: 'cicloId', type: 'integer', nullable: true), new OA\Property(property: 'casoId', type: 'integer', nullable: true)])),
        responses: [
            new OA\Response(response: 201, description: 'Tratamiento nuevo creado', content: new OA\JsonContent(ref: '#/components/schemas/Tratamiento')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflicto'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    /**
     * Ajuste de ciclo: emite una version nueva encadenada al tratamiento firmado.
     * El original queda intacto como historial clinico.
     */
    public function reemplazar(Request $request, int $id): JsonResponse
    {
        $this->autorizarEscritura($request);

        $anterior = $this->consulta($request)->findOrFail($id);

        abort_unless($anterior->estaFirmado(), 409, 'Solo se reemplaza un tratamiento firmado.');
        abort_if($anterior->reemplazo()->exists(), 409, 'Este tratamiento ya fue reemplazado.');

        $datos = $request->validate([
            'descripcion' => ['required', 'string', 'max:255'],
            'tratamientoAI' => ['nullable', 'string'],
            'tratamientoDoctor' => ['nullable', 'string'],
            'notaDoctor' => ['nullable', 'string'],
            'cicloId' => ['nullable', 'exists:ciclos,id'],
            'casoId' => ['nullable', 'exists:casos,id'],
        ]);

        $nuevo = DB::transaction(function () use ($request, $anterior, $datos) {
            $nuevo = Tratamiento::create([
                ...$datos,
                'pacienteId' => $anterior->pacienteId,
                'doctorId' => Alcance::doctor($request->user())?->id,
                'estado' => 'borrador',
                'version' => $anterior->version + 1,
                'reemplazaA' => $anterior->id,
            ]);

            $this->auditar($request, 'reemplazar', $nuevo, $anterior->toArray(), $nuevo->toArray());

            return $nuevo;
        });

        return response()->json($nuevo->load($this->with), 201);
    }
}
