<?php

namespace App\Http\Controllers;

use App\Models\Elegibilidad;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ElegibilidadController extends BaseCrudController
{
    protected string $modelo = Elegibilidad::class;

    protected array $rolesEscritura = [User::ROL_ADMIN, User::ROL_DOCTOR];

    protected ?string $columnaPaciente = 'pacienteId';

    protected array $filtrables = ['pacienteId', 'semaforo'];

    protected array $ordenables = ['id', 'generadaEn'];

    protected string $ordenPorDefecto = 'generadaEn';

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'pacienteId' => [$req, 'exists:pacientes,id'],
            'semaforo' => [$req, 'in:verde,amarillo,rojo'],
            'resumen' => ['nullable', 'string'],
            'criterios' => ['nullable', 'array'],
            'generadaEn' => ['sometimes', 'date'],
            'vigenciaHasta' => ['nullable', 'date', 'after:today'],
        ];
    }

    protected function antesDeCrear(Request $request, array $datos): array
    {
        $datos['generadaEn'] ??= now();
        // Numero de informe visible para el paciente, unico e irrepetible.
        $datos['nroInforme'] = strtoupper(Str::random(2)).now()->format('y').str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);

        return $datos;
    }
}
