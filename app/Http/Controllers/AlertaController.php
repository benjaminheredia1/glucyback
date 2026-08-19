<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\User;
use App\Support\Alcance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AlertaController extends BaseCrudController
{
    protected string $modelo = Alerta::class;

    protected array $rolesEscritura = [User::ROL_ADMIN, User::ROL_DOCTOR];

    protected array $with = ['medicion', 'paciente.usuario'];

    protected ?string $columnaPaciente = 'pacienteId';

    protected array $filtrables = ['pacienteId', 'tipo', 'severidad', 'estado'];

    protected array $ordenables = ['id', 'created_at', 'severidad'];

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'pacienteId' => [$req, 'exists:pacientes,id'],
            'medicionId' => ['nullable', 'exists:mediciones,id'],
            'reglaId' => ['nullable', 'exists:reglas_alerta,id'],
            'casoId' => ['nullable', 'exists:casos,id'],
            'tipo' => [$req, 'in:valor_critico,sin_registro,estudio_vencido,ciclo_vencido'],
            'severidad' => [$req, 'in:critica,alta,media'],
            'mensaje' => [$req, 'string', 'max:255'],
            'estado' => ['sometimes', 'in:abierta,vista,atendida'],
        ];
    }

    #[OA\Post(
        path: '/alertas/{id}/atender',
        tags: ['Alerta'],
        summary: 'Marcar alerta como atendida',
        description: 'Registra quien la atendio (doctor de la sesion) y cuando. Falla si ya estaba atendida.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Alerta atendida', content: new OA\JsonContent(ref: '#/components/schemas/Alerta')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflicto'),
        ],
    )]
    public function atender(Request $request, int $id): JsonResponse
    {
        $this->autorizarEscritura($request);

        $alerta = $this->consulta($request)->findOrFail($id);

        abort_if($alerta->estado === 'atendida', 409, 'La alerta ya fue atendida.');

        $antes = $alerta->toArray();

        $alerta->update([
            'estado' => 'atendida',
            'atendidaPor' => Alcance::doctor($request->user())?->id,
            'atendidaEn' => now(),
        ]);

        $this->auditar($request, 'atender', $alerta, $antes, $alerta->toArray());

        return response()->json($alerta->fresh($this->with));
    }
}
