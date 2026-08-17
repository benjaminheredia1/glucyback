<?php

namespace App\Http\Controllers;

use App\Models\ReglaAlerta;
use App\Models\User;
use Illuminate\Http\Request;

class ReglaAlertaController extends BaseCrudController
{
    protected string $modelo = ReglaAlerta::class;

    protected array $rolesLectura = [User::ROL_ADMIN, User::ROL_DOCTOR];

    protected array $rolesEscritura = [User::ROL_ADMIN];

    protected ?string $columnaClinica = 'clinicaId';

    protected array $filtrables = ['clinicaId', 'momento', 'severidad', 'activa'];

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'clinicaId' => ['nullable', 'exists:clinicas,id'],
            'momento' => ['sometimes', 'in:ayunas,preprandial,postprandial,nocturna,cualquiera'],
            'minimo' => ['nullable', 'numeric', 'required_without:maximo'],
            'maximo' => ['nullable', 'numeric', 'gt:minimo'],
            'severidad' => [$req, 'in:critica,alta,media'],
            'mensaje' => [$req, 'string', 'max:255'],
            'activa' => ['sometimes', 'boolean'],
        ];
    }
}
