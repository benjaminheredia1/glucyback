<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;

class PlanController extends BaseCrudController
{
    protected string $modelo = Plan::class;

    protected array $rolesEscritura = [User::ROL_ADMIN];

    protected array $filtrables = ['ambito', 'periodicidad', 'activo'];

    protected array $ordenables = ['id', 'nombre', 'precio'];

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'nombre' => [$req, 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'ambito' => [$req, 'in:paciente,clinica'],
            'precio' => [$req, 'numeric', 'min:0'],
            'moneda' => ['sometimes', 'string', 'size:3'],
            'periodicidad' => [$req, 'in:mensual,anual'],
            'consultasIncluidas' => ['sometimes', 'integer', 'min:0'],
            'diasPrueba' => ['sometimes', 'integer', 'min:0'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }
}
