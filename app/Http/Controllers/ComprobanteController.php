<?php

namespace App\Http\Controllers;

use App\Models\Comprobante;
use App\Models\User;
use Illuminate\Http\Request;

class ComprobanteController extends BaseCrudController
{
    protected string $modelo = Comprobante::class;

    protected array $rolesEscritura = [User::ROL_ADMIN];

    protected array $with = ['archivo'];

    protected ?array $pacienteViaRelacion = ['relacion' => 'pago.suscripcion', 'columna' => 'pacienteId'];

    protected array $filtrables = ['pagoId'];

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';
        $id = $request->route('id');

        return [
            'pagoId' => [$req, 'exists:pagos,id'],
            'numero' => [$req, 'string', 'max:255', 'unique:comprobantes,numero'.($id ? ",{$id}" : '')],
            'archivoId' => ['nullable', 'exists:archivos,id'],
            'emitidoEn' => ['sometimes', 'date'],
        ];
    }

    protected function antesDeCrear(Request $request, array $datos): array
    {
        $datos['emitidoEn'] ??= now();

        return $datos;
    }
}
