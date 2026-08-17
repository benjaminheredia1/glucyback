<?php

namespace App\Http\Controllers;

use App\Models\TipoEstudio;
use App\Models\User;
use Illuminate\Http\Request;

class TipoEstudioController extends BaseCrudController
{
    protected string $modelo = TipoEstudio::class;

    protected array $rolesEscritura = [User::ROL_ADMIN];

    protected array $filtrables = ['esObligatorio'];

    protected array $ordenables = ['id', 'orden', 'nombre'];

    protected string $ordenPorDefecto = 'orden';

    protected string $direccionPorDefecto = 'asc';

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'nombre' => [$req, 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'unidad' => ['nullable', 'string', 'max:50'],
            'rangoMin' => ['nullable', 'numeric'],
            'rangoMax' => ['nullable', 'numeric', 'gte:rangoMin'],
            'esObligatorio' => ['sometimes', 'boolean'],
            'orden' => ['sometimes', 'integer', 'min:0'],
            'codigoLoinc' => ['nullable', 'string', 'max:50'],
        ];
    }
}
