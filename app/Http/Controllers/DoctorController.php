<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DoctorController extends BaseCrudController
{
    protected string $modelo = Doctor::class;

    protected array $rolesEscritura = [User::ROL_ADMIN];

    protected array $with = ['usuario', 'clinica'];

    protected ?string $columnaClinica = 'clinicaId';

    protected array $filtrables = ['clinicaId', 'estado', 'especialidad'];

    protected array $ordenables = ['id', 'matricula', 'created_at'];

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';
        $id = $request->route('id');

        return [
            'usuarioId' => [$req, 'exists:users,id', Rule::unique('doctores', 'usuarioId')->ignore($id)],
            'clinicaId' => [$req, 'exists:clinicas,id'],
            'matricula' => [$req, 'string', 'max:255', Rule::unique('doctores', 'matricula')->ignore($id)],
            'especialidad' => ['nullable', 'string', 'max:255'],
            'firmaArchivoId' => ['nullable', 'exists:archivos,id'],
            'estado' => ['sometimes', 'in:activo,inactivo'],
        ];
    }
}
