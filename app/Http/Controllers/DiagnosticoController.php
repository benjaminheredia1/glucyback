<?php

namespace App\Http\Controllers;

use App\Models\Diagnostico;
use App\Models\User;
use App\Support\Alcance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DiagnosticoController extends BaseCrudController
{
    protected string $modelo = Diagnostico::class;

    protected array $rolesEscritura = [User::ROL_ADMIN, User::ROL_DOCTOR];

    protected array $with = ['doctor.usuario', 'caso', 'ciclo'];

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
            'diagnosticoAI' => ['nullable', 'string'],
            'diagnosticoDoctor' => ['nullable', 'string'],
            'estado' => ['sometimes', 'in:borrador,pendiente_firma'],
        ];
    }

    /** Un documento firmado es inmutable. */
    protected function antesDeActualizar(Request $request, Model $registro, array $datos): array
    {
        abort_if(
            $registro->estaFirmado(),
            409,
            'Un diagnostico firmado no se edita: emiti una version nueva.'
        );

        unset($datos['firmadoEn'], $datos['version']);

        return $datos;
    }

    protected function antesDeEliminar(Request $request, Model $registro): void
    {
        abort_if($registro->estaFirmado(), 409, 'Un diagnostico firmado no se puede eliminar.');
    }

    #[OA\Post(
        path: '/diagnosticos/{id}/firmar',
        tags: ['Diagnostico'],
        summary: 'Firmar diagnostico',
        description: 'Lo firma el doctor de la sesion. Falla si ya esta firmado o si el doctor no corresponde.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Diagnostico firmado', content: new OA\JsonContent(ref: '#/components/schemas/Diagnostico')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflicto'),
        ],
    )]
    public function firmar(Request $request, int $id): JsonResponse
    {
        $this->autorizarEscritura($request);

        $diagnostico = $this->consulta($request)->findOrFail($id);

        abort_if($diagnostico->estaFirmado(), 409, 'El diagnostico ya esta firmado.');

        $doctor = Alcance::doctor($request->user());

        abort_if(
            $doctor === null && ! $request->user()->esAdmin(),
            422,
            'Solo un doctor con matricula puede firmar.'
        );

        abort_if(
            blank($diagnostico->diagnosticoDoctor),
            422,
            'No se puede firmar un diagnostico sin texto del doctor.'
        );

        $antes = $diagnostico->toArray();

        $diagnostico->update([
            'doctorId' => $doctor?->id ?? $diagnostico->doctorId,
            'estado' => 'firmado',
            'firmadoEn' => now(),
        ]);

        $this->auditar($request, 'firmar', $diagnostico, $antes, $diagnostico->toArray());

        return response()->json($diagnostico->fresh($this->with));
    }
}
