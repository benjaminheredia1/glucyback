<?php

namespace App\Http\Controllers;

use App\Models\DoctorPaciente;
use App\Models\User;
use Illuminate\Http\Request;

class DoctorPacienteController extends BaseCrudController
{
    protected string $modelo = DoctorPaciente::class;

    protected array $rolesLectura = [User::ROL_ADMIN, User::ROL_DOCTOR];

    protected array $rolesEscritura = [User::ROL_ADMIN, User::ROL_DOCTOR];

    protected array $with = ['doctor.usuario', 'paciente.usuario'];

    protected ?string $columnaPaciente = 'pacienteId';

    protected array $filtrables = ['doctorId', 'pacienteId', 'activo'];

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'doctorId' => [$req, 'exists:doctores,id'],
            'pacienteId' => [$req, 'exists:pacientes,id'],
            'desde' => [$req, 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }
}
