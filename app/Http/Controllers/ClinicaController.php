<?php

namespace App\Http\Controllers;

use App\Models\Clinica;
use App\Models\User;
use Illuminate\Http\Request;

class ClinicaController extends BaseCrudController
{
    protected string $modelo = Clinica::class;

    protected array $rolesLectura = [User::ROL_ADMIN, User::ROL_DOCTOR, User::ROL_PACIENTE];

    protected array $rolesEscritura = [User::ROL_ADMIN];

    protected array $with = ['plan'];

    protected ?string $columnaClinica = 'id';

    protected array $filtrables = ['estado', 'planId'];

    protected array $ordenables = ['id', 'nombre', 'estado'];

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'nombre' => [$req, 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'direccion' => [$req, 'string', 'max:255'],
            'telefono' => [$req, 'string', 'max:50'],
            'estado' => ['sometimes', 'in:activa,pago_pendiente,suspendida'],
            'nit' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'planId' => ['nullable', 'exists:planes,id'],
            'usuarioId' => [$req, 'exists:users,id'],
        ];
    }
}
