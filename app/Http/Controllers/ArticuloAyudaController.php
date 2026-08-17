<?php

namespace App\Http\Controllers;

use App\Models\ArticuloAyuda;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ArticuloAyudaController extends BaseCrudController
{
    protected string $modelo = ArticuloAyuda::class;

    protected array $rolesEscritura = [User::ROL_ADMIN];

    protected array $filtrables = ['categoria', 'publicado'];

    protected array $ordenables = ['id', 'orden', 'titulo'];

    protected string $ordenPorDefecto = 'orden';

    protected string $direccionPorDefecto = 'asc';

    /** Los borradores solo los ve el admin. */
    protected function aplicarAlcance(Builder $consulta, ?User $usuario): void
    {
        if ($usuario === null || $usuario->esAdmin()) {
            return;
        }

        $consulta->where('publicado', true);
    }

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'categoria' => [$req, 'string', 'max:100'],
            'titulo' => [$req, 'string', 'max:255'],
            'cuerpo' => [$req, 'string'],
            'orden' => ['sometimes', 'integer', 'min:0'],
            'publicado' => ['sometimes', 'boolean'],
        ];
    }
}
