<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
