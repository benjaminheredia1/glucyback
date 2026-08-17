<?php

namespace App\Http\Controllers;

use App\Models\Licencia;
use App\Models\Plan;
use App\Models\Suscripcion;
use App\Models\User;
use Illuminate\Http\Request;

class SuscripcionController extends BaseCrudController
{
    protected string $modelo = Suscripcion::class;

    protected array $rolesEscritura = [User::ROL_ADMIN];

    protected array $with = ['plan', 'licencia'];

    protected ?string $columnaPaciente = 'pacienteId';

    protected array $filtrables = ['pacienteId', 'planId', 'licenciaId', 'estado'];

    protected array $ordenables = ['id', 'inicio', 'proximoCobro'];

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'pacienteId' => [$req, 'exists:pacientes,id'],
            'planId' => [$req, 'exists:planes,id'],
            'licenciaId' => ['nullable', 'exists:licencias,id'],
            'estado' => ['sometimes', 'in:prueba,activa,vencida,cancelada'],
            'inicio' => ['sometimes', 'date'],
            'fin' => ['nullable', 'date', 'after:inicio'],
            'proximoCobro' => ['nullable', 'date'],
            'consultasUsadas' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    protected function antesDeCrear(Request $request, array $datos): array
    {
        $plan = Plan::findOrFail($datos['planId']);

        $datos['inicio'] ??= now()->toDateString();

        // Los dias de prueba del plan definen el estado inicial y el primer cobro.
        if (! isset($datos['estado'])) {
            $datos['estado'] = $plan->diasPrueba > 0 ? 'prueba' : 'activa';
        }

        $datos['proximoCobro'] ??= now()
            ->addDays($plan->diasPrueba)
            ->toDateString();

        if (! empty($datos['licenciaId'])) {
            $licencia = Licencia::findOrFail($datos['licenciaId']);

            abort_if(
                $licencia->cuposDisponibles() < 1,
                422,
                'La licencia no tiene cupos disponibles.'
            );

            $licencia->increment('usadas');
        }

        return $datos;
    }
}
