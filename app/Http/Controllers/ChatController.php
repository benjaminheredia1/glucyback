<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Http\Request;

class ChatController extends BaseCrudController
{
    protected string $modelo = Chat::class;

    protected array $rolesEscritura = [User::ROL_ADMIN, User::ROL_DOCTOR, User::ROL_PACIENTE];

    protected ?string $columnaPaciente = 'pacienteId';

    protected bool $forzarPacientePropio = true;

    protected array $filtrables = ['pacienteId', 'tipo', 'estado'];

    protected array $ordenables = ['id', 'ultimoMensajeEn'];

    protected string $ordenPorDefecto = 'ultimoMensajeEn';

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'nombre' => [$req, 'string', 'max:255'],
            'pacienteId' => ['nullable', 'exists:pacientes,id'],
            'tipo' => ['sometimes', 'in:soporte,clinico'],
            'estado' => ['sometimes', 'in:abierto,cerrado'],
        ];
    }
}
