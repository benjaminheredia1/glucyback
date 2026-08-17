<?php

namespace App\Http\Controllers;

use App\Models\Licencia;
use App\Models\LicenciaUsuario;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LicenciaUsuarioController extends BaseCrudController
{
    protected string $modelo = LicenciaUsuario::class;

    protected array $rolesLectura = [User::ROL_ADMIN, User::ROL_DOCTOR];

    protected array $rolesEscritura = [User::ROL_ADMIN];

    protected array $with = ['usuario', 'licencia'];

    protected array $filtrables = ['licenciaId', 'usuarioId', 'estado'];

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'licenciaId' => [$req, 'exists:licencias,id'],
            'usuarioId' => [
                $req,
                'exists:users,id',
                Rule::unique('licencia_usuarios', 'usuarioId')
                    ->where('licenciaId', $request->input('licenciaId'))
                    ->ignore($request->route('id')),
            ],
            'estado' => ['sometimes', 'in:activa,revocada'],
        ];
    }

    protected function antesDeCrear(Request $request, array $datos): array
    {
        $licencia = Licencia::findOrFail($datos['licenciaId']);

        abort_if($licencia->cuposDisponibles() < 1, 422, 'La licencia no tiene cupos disponibles.');
        abort_if($licencia->estaVencida(), 422, 'La licencia esta vencida.');
        abort_unless($licencia->estado === 'activa', 422, 'La licencia no esta activa.');

        $datos['estado'] = 'activa';
        $datos['asignadoEn'] = now();

        $licencia->increment('usadas');

        return $datos;
    }

    protected function antesDeActualizar(Request $request, Model $registro, array $datos): array
    {
        // Revocar devuelve el cupo a la licencia.
        if (($datos['estado'] ?? null) === 'revocada' && $registro->estado !== 'revocada') {
            $datos['revocadoEn'] = now();
            Licencia::whereKey($registro->licenciaId)->where('usadas', '>', 0)->decrement('usadas');
        }

        return $datos;
    }

    protected function antesDeEliminar(Request $request, Model $registro): void
    {
        if ($registro->estado === 'activa') {
            Licencia::whereKey($registro->licenciaId)->where('usadas', '>', 0)->decrement('usadas');
        }
    }
}
