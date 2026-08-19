<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PagoController extends BaseCrudController
{
    protected string $modelo = Pago::class;

    protected array $rolesEscritura = [User::ROL_ADMIN];

    protected array $with = ['comprobante'];

    protected ?array $pacienteViaRelacion = ['relacion' => 'suscripcion', 'columna' => 'pacienteId'];

    protected array $filtrables = ['suscripcionId', 'estado', 'metodo'];

    protected array $ordenables = ['id', 'pagadoEn', 'monto'];

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'suscripcionId' => [$req, 'exists:suscripciones,id'],
            'monto' => [$req, 'numeric', 'min:0'],
            'moneda' => ['sometimes', 'string', 'size:3'],
            'metodo' => [$req, 'in:qr,tarjeta,transferencia'],
            'referencia' => ['nullable', 'string', 'max:255', 'unique:pagos,referencia'],
        ];
    }

    protected function antesDeCrear(Request $request, array $datos): array
    {
        $datos['estado'] = 'pendiente';

        return $datos;
    }

    /** El estado de un pago lo mueve la confirmacion del proveedor, no un PATCH. */
    protected function antesDeActualizar(Request $request, Model $registro, array $datos): array
    {
        unset($datos['estado'], $datos['pagadoEn']);

        return $datos;
    }

    #[OA\Post(
        path: '/pagos/{id}/confirmar',
        tags: ['Pago'],
        summary: 'Confirmar resultado de un pago pendiente',
        description: 'Solo sobre pagos en estado pendiente.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['estado'], properties: [new OA\Property(property: 'estado', type: 'string', enum: ['pagado', 'fallido']), new OA\Property(property: 'referencia', type: 'string', maxLength: 255, nullable: true)])),
        responses: [
            new OA\Response(response: 200, description: 'Pago confirmado', content: new OA\JsonContent(ref: '#/components/schemas/Pago')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflicto'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function confirmar(Request $request, int $id): JsonResponse
    {
        $this->autorizarEscritura($request);

        $pago = $this->consulta($request)->findOrFail($id);

        abort_unless($pago->estado === 'pendiente', 409, 'El pago ya no esta pendiente.');

        $datos = $request->validate([
            'estado' => ['required', 'in:pagado,fallido'],
            'referencia' => ['nullable', 'string', 'max:255'],
        ]);

        $antes = $pago->toArray();

        $pago->update([
            'estado' => $datos['estado'],
            'referencia' => $datos['referencia'] ?? $pago->referencia,
            'pagadoEn' => $datos['estado'] === 'pagado' ? now() : null,
        ]);

        if ($datos['estado'] === 'pagado') {
            $pago->suscripcion?->update(['estado' => 'activa']);
        }

        $this->auditar($request, 'confirmar', $pago, $antes, $pago->toArray());

        return response()->json($pago->fresh($this->with));
    }
}
