<?php

namespace App\Http\Controllers;

use App\Models\AntecedenteFamiliar;
use App\Models\User;
use Illuminate\Http\Request;

class AntecedenteFamiliarController extends BaseCrudController
{
    protected string $modelo = AntecedenteFamiliar::class;

    protected array $rolesEscritura = [User::ROL_ADMIN, User::ROL_DOCTOR, User::ROL_PACIENTE];

    protected ?string $columnaPaciente = 'pacienteId';

    protected bool $forzarPacientePropio = true;

    protected array $filtrables = ['pacienteId', 'condicion'];

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'pacienteId' => [$req, 'exists:pacientes,id'],
            'condicion' => [$req, 'string', 'max:255'],
            'parentesco' => ['nullable', 'string', 'max:255'],
        ];
    }
}
