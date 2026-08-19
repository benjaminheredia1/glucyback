<?php

namespace App\Http\Controllers;

use App\Models\Licencia;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class LicenciaController extends BaseCrudController
{
    protected string $modelo = Licencia::class;

    protected array $rolesLectura = [User::ROL_ADMIN, User::ROL_DOCTOR];

    protected array $rolesEscritura = [User::ROL_ADMIN];

    protected array $with = ['clinica', 'plan'];

    protected ?string $columnaClinica = 'clinicaId';

    protected array $filtrables = ['clinicaId', 'planId', 'estado'];

    protected array $ordenables = ['id', 'fecha_expiracion', 'codigo'];

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';
        $id = $request->route('id');

        return [
            'codigo' => [$req, 'string', 'max:255', Rule::unique('licencias', 'codigo')->ignore($id)],
            'nombre' => [$req, 'string', 'max:255'],
            'clinicaId' => [$req, 'exists:clinicas,id'],
            'planId' => ['nullable', 'exists:planes,id'],
            'cantidad' => [$req, 'integer', 'min:1'],
            'fecha_expiracion' => [$req, 'date'],
            'descuento' => [$req, 'numeric', 'between:0,100'],
            'estado' => ['sometimes', 'in:activa,inactiva,suspendida,vencida'],
        ];
    }

    /** `usadas` es un contador derivado de las asignaciones, no un campo editable. */
    protected function antesDeActualizar(Request $request, Model $registro, array $datos): array
    {
        unset($datos['usadas']);

        if (isset($datos['cantidad'])) {
            abort_if(
                $datos['cantidad'] < $registro->usadas,
                422,
                "La licencia ya tiene {$registro->usadas} cupos asignados."
            );
        }

        return $datos;
    }

    #[OA\Post(
        path: '/licencias/{id}/suspender',
        tags: ['Licencia'],
        summary: 'Suspender licencia',
        description: 'Deja la licencia suspendida.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Licencia suspendida', content: new OA\JsonContent(ref: '#/components/schemas/Licencia')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflicto'),
        ],
    )]
    public function suspender(Request $request, int $id): JsonResponse
    {
        $this->autorizarEscritura($request);

        $licencia = $this->consulta($request)->findOrFail($id);

        $antes = $licencia->toArray();
        $nuevo = $licencia->estado === 'suspendida' ? 'activa' : 'suspendida';

        $licencia->update(['estado' => $nuevo]);

        $this->auditar($request, $nuevo === 'suspendida' ? 'suspender' : 'reactivar', $licencia, $antes, $licencia->toArray());

        return response()->json($licencia->fresh($this->with));
    }
}
