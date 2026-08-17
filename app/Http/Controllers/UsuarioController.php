<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UsuarioController extends BaseCrudController
{
    protected string $modelo = User::class;

    protected array $rolesLectura = [User::ROL_ADMIN];

    protected array $rolesEscritura = [User::ROL_ADMIN];

    protected array $with = ['doctor', 'paciente'];

    protected array $filtrables = ['rol'];

    protected array $ordenables = ['id', 'name', 'email', 'created_at'];

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';
        $id = $request->route('id');

        return [
            'name' => [$req, 'string', 'max:255'],
            'apellidoPaterno' => ['nullable', 'string', 'max:255'],
            'apellidoMaterno' => ['nullable', 'string', 'max:255'],
            'email' => [$req, 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'telefono' => ['nullable', 'string', 'max:50'],
            'password' => $creando
                ? ['required', 'confirmed', Password::defaults()]
                : ['sometimes', 'confirmed', Password::defaults()],
            'rol' => [$req, 'in:admin,doctor,paciente'],
        ];
    }
}
