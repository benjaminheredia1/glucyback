<?php

namespace App\Http\Controllers;

use App\Models\Dispositivo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DispositivoController extends BaseCrudController
{
    protected string $modelo = Dispositivo::class;

    protected array $rolesEscritura = [User::ROL_ADMIN, User::ROL_DOCTOR, User::ROL_PACIENTE];

    protected array $filtrables = ['plataforma'];

    /** Un dispositivo pertenece al usuario, no a un paciente. */
    protected function aplicarAlcance(Builder $consulta, ?User $usuario): void
    {
        if ($usuario === null || $usuario->esAdmin()) {
            return;
        }

        $consulta->where('usuarioId', $usuario->id);
    }

    protected function antesDeCrear(Request $request, array $datos): array
    {
        $datos['usuarioId'] = $request->user()->id;

        return $datos;
    }

    protected function antesDeActualizar(Request $request, Model $registro, array $datos): array
    {
        unset($datos['usuarioId']);

        return $datos;
    }

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';
        $id = $request->route('id');

        return [
            'pushToken' => [$req, 'string', 'max:255', Rule::unique('dispositivos', 'pushToken')->ignore($id)],
            'plataforma' => [$req, 'in:ios,android,web'],
            'modelo' => ['nullable', 'string', 'max:255'],
            'ultimoUsoEn' => ['nullable', 'date'],
        ];
    }
}
