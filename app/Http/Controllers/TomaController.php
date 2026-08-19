<?php

namespace App\Http\Controllers;

use App\Models\Toma;
use App\Models\User;
use App\Support\Alcance;
use App\Support\Tomas;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class TomaController extends BaseCrudController
{
    protected string $modelo = Toma::class;

    protected array $rolesEscritura = [User::ROL_ADMIN, User::ROL_DOCTOR, User::ROL_PACIENTE];

    protected array $with = ['pacienteMedicamento.medicamento'];

    protected ?array $pacienteViaRelacion = ['relacion' => 'pacienteMedicamento', 'columna' => 'pacienteId'];

    protected array $filtrables = ['pacienteMedicamentoId', 'estado'];

    protected array $ordenables = ['id', 'programadaEn'];

    protected string $ordenPorDefecto = 'programadaEn';

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'pacienteMedicamentoId' => [$req, 'exists:paciente_medicamentos,id'],
            'programadaEn' => [$req, 'date'],
            'tomadaEn' => ['nullable', 'date'],
            'estado' => ['sometimes', 'in:pendiente,tomada,omitida'],
        ];
    }

    /**
     * `GET /tomas?dia=Y-m-d&zona=<IANA>[&pacienteId=]`: antes de listar,
     * materializa las tomas pendientes de ese dia para el paciente objetivo
     * (el propio, o el `pacienteId` visible para doctor/admin) y filtra por el
     * rango del dia en esa zona. Sin `dia`, hoy; sin `zona`, la de la app.
     */
    public function listar(Request $request): JsonResponse
    {
        $this->autorizarLectura($request);

        $request->validate([
            'dia' => ['nullable', 'date_format:Y-m-d'],
            'zona' => ['nullable', 'timezone:all'],
            'pacienteId' => ['nullable', 'integer'],
        ]);

        [$inicio, $zona] = $this->rangoDelDia($request);

        $pacienteId = Alcance::pacienteObjetivo(
            $request->user(),
            $request->filled('pacienteId') ? (int) $request->query('pacienteId') : null,
        );

        if ($pacienteId !== null) {
            Tomas::materializar($pacienteId, $inicio, $zona);
        }

        return parent::listar($request);
    }

    protected function filtrarListado(Request $request, Builder $consulta): void
    {
        [$inicio] = $this->rangoDelDia($request);

        $consulta->whereBetween('programadaEn', [$inicio->utc(), $inicio->endOfDay()->utc()]);
    }

    /** @return array{0: CarbonImmutable, 1: string} inicio del dia en la zona pedida, y la zona. */
    private function rangoDelDia(Request $request): array
    {
        $zona = (string) $request->query('zona', config('app.timezone'));
        $inicio = CarbonImmutable::parse((string) $request->query('dia', 'today'), $zona)->startOfDay();

        return [$inicio, $zona];
    }

    #[OA\Post(
        path: '/tomas/{id}/marcar',
        tags: ['Toma'],
        summary: 'Marcar una toma como tomada u omitida',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['estado'], properties: [new OA\Property(property: 'estado', type: 'string', enum: ['tomada', 'omitida'])])),
        responses: [
            new OA\Response(response: 200, description: 'Toma marcada', content: new OA\JsonContent(ref: '#/components/schemas/Toma')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflicto'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function marcar(Request $request, int $id): JsonResponse
    {
        $this->autorizarEscritura($request);

        $toma = $this->consulta($request)->findOrFail($id);

        $datos = $request->validate([
            'estado' => ['required', 'in:tomada,omitida'],
        ]);

        $antes = $toma->toArray();

        $toma->update([
            'estado' => $datos['estado'],
            'tomadaEn' => $datos['estado'] === 'tomada' ? now() : null,
        ]);

        $this->auditar($request, 'marcar', $toma, $antes, $toma->toArray());

        return response()->json($toma->fresh($this->with));
    }
}
