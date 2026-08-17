<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PacienteController extends BaseCrudController
{
    protected string $modelo = Paciente::class;

    protected array $rolesEscritura = [User::ROL_ADMIN, User::ROL_DOCTOR];

    protected array $with = ['usuario', 'clinica', 'elegibilidadVigente'];

    protected ?string $columnaPaciente = 'id';

    protected array $filtrables = ['clinicaId', 'tipoDiabetes', 'sexo'];

    protected array $ordenables = ['id', 'created_at'];

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';
        $id = $request->route('id');

        return [
            'usuarioId' => [$req, 'exists:users,id', Rule::unique('pacientes', 'usuarioId')->ignore($id)],
            'clinicaId' => ['nullable', 'exists:clinicas,id'],
            'fechaNacimiento' => [$req, 'date', 'before:today'],
            'sexo' => ['nullable', 'in:femenino,masculino,otro'],
            'tipoDiabetes' => [$req, 'string', 'max:255'],
            'pesoKg' => ['nullable', 'numeric', 'between:1,400'],
            'tallaCm' => ['nullable', 'integer', 'between:30,260'],
            'diagnosticadoEn' => ['nullable', 'date'],
            'alergias' => ['nullable', 'string'],
            'comorbilidades' => ['nullable', 'string'],
            'tabaquismo' => ['sometimes', 'boolean'],
            'contactoEmergencia' => ['nullable', 'string', 'max:255'],
        ];
    }
}
