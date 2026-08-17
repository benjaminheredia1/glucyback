<?php

namespace App\Http\Controllers;

use App\Models\CitaLaboratorio;
use App\Models\User;
use Illuminate\Http\Request;

class CitaLaboratorioController extends BaseCrudController
{
    protected string $modelo = CitaLaboratorio::class;

    protected array $rolesEscritura = [User::ROL_ADMIN, User::ROL_DOCTOR, User::ROL_PACIENTE];

    protected array $with = ['laboratorio'];

    protected ?string $columnaPaciente = 'pacienteId';

    protected bool $forzarPacientePropio = true;

    protected array $filtrables = ['pacienteId', 'laboratorioId', 'estado', 'franja'];

    protected array $ordenables = ['id', 'fecha'];

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'pacienteId' => [$req, 'exists:pacientes,id'],
            'laboratorioId' => [$req, 'exists:laboratorios,id'],
            'fecha' => [$req, 'date', 'after_or_equal:today'],
            'franja' => [$req, 'in:manana,tarde'],
            'direccion' => [$req, 'string', 'max:255'],
            'referencia' => ['nullable', 'string', 'max:255'],
            'estado' => ['sometimes', 'in:agendada,realizada,cancelada'],
        ];
    }
}
