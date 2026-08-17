<?php

namespace App\Http\Controllers;

use App\Models\Ciclo;
use App\Models\User;
use Illuminate\Http\Request;

class CicloController extends BaseCrudController
{
    protected string $modelo = Ciclo::class;

    protected array $rolesEscritura = [User::ROL_ADMIN, User::ROL_DOCTOR];

    protected ?string $columnaPaciente = 'pacienteId';

    protected array $filtrables = ['pacienteId', 'estado', 'numero'];

    protected array $ordenables = ['id', 'numero', 'inicio'];

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'pacienteId' => [$req, 'exists:pacientes,id'],
            'numero' => ['sometimes', 'integer', 'min:1'],
            'inicio' => [$req, 'date'],
            'fin' => [$req, 'date', 'after:inicio'],
            'medicionesRequeridas' => ['sometimes', 'integer', 'min:1'],
            'estado' => ['sometimes', 'in:activo,completo,vencido'],
        ];
    }

    protected function antesDeCrear(Request $request, array $datos): array
    {
        $datos['numero'] ??= Ciclo::withTrashed()
            ->where('pacienteId', $datos['pacienteId'])
            ->max('numero') + 1;

        return $datos;
    }
}
