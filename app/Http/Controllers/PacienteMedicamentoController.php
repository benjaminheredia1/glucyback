<?php

namespace App\Http\Controllers;

use App\Models\PacienteMedicamento;
use App\Models\User;
use Illuminate\Http\Request;

class PacienteMedicamentoController extends BaseCrudController
{
    protected string $modelo = PacienteMedicamento::class;

    protected array $rolesEscritura = [User::ROL_ADMIN, User::ROL_DOCTOR];

    protected array $with = ['medicamento', 'tratamiento'];

    protected ?string $columnaPaciente = 'pacienteId';

    protected array $filtrables = ['pacienteId', 'medicamentoId', 'tratamientoId', 'activo'];

    protected array $ordenables = ['id', 'fechaInicio'];

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'pacienteId' => [$req, 'exists:pacientes,id'],
            'tratamientoId' => ['nullable', 'exists:tratamientos,id'],
            'medicamentoId' => [$req, 'exists:medicamentos,id'],
            'dosis' => [$req, 'string', 'max:255'],
            'frecuencia' => [$req, 'string', 'max:255'],
            // Horas del dia en las que toca la toma. La app materializa las
            // tomas diarias a partir de esta lista.
            'horarios' => [$req, 'array', 'min:1', 'max:6'],
            'horarios.*' => ['required', 'date_format:H:i', 'distinct'],
            'indicaciones' => ['nullable', 'string'],
            'fechaInicio' => [$req, 'date'],
            'fechaFin' => ['nullable', 'date', 'after_or_equal:fechaInicio'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }
}
