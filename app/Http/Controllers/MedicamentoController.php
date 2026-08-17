<?php

namespace App\Http\Controllers;

use App\Models\Medicamento;
use App\Models\User;
use Illuminate\Http\Request;

class MedicamentoController extends BaseCrudController
{
    protected string $modelo = Medicamento::class;

    protected array $rolesEscritura = [User::ROL_ADMIN];

    protected array $filtrables = ['activo'];

    protected array $ordenables = ['id', 'nombre'];

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'nombre' => [$req, 'string', 'max:255'],
            'concentracion' => ['nullable', 'string', 'max:100'],
            'presentacion' => ['nullable', 'string', 'max:100'],
            'viaAdministracion' => ['nullable', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }
}
