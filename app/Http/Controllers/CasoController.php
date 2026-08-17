<?php

namespace App\Http\Controllers;

use App\Models\Caso;
use App\Models\User;
use App\Support\Alcance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Bandeja del doctor. `abiertoEn` / `asignadoEn` / `cerradoEn` son la fuente del
 * SLA que el panel del admin grafica por clinica.
 */
class CasoController extends BaseCrudController
{
    protected string $modelo = Caso::class;

    protected array $rolesLectura = [User::ROL_ADMIN, User::ROL_DOCTOR];

    protected array $rolesEscritura = [User::ROL_ADMIN, User::ROL_DOCTOR];

    protected array $with = ['paciente.usuario', 'doctor.usuario', 'ciclo'];

    protected ?string $columnaPaciente = 'pacienteId';

    protected array $filtrables = ['pacienteId', 'doctorId', 'tipo', 'urgencia', 'estado', 'cicloId'];

    protected array $ordenables = ['id', 'abiertoEn', 'urgencia', 'cerradoEn'];

    protected string $ordenPorDefecto = 'abiertoEn';

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'pacienteId' => [$req, 'exists:pacientes,id'],
            'doctorId' => ['nullable', 'exists:doctores,id'],
            'cicloId' => ['nullable', 'exists:ciclos,id'],
            'tipo' => [$req, 'in:ingreso,ajuste_ciclo,revision_15d,alerta'],
            'urgencia' => ['sometimes', 'in:urgente,pendiente,estable'],
            'titulo' => [$req, 'string', 'max:255'],
            'nota' => ['nullable', 'string'],
        ];
    }

    protected function antesDeCrear(Request $request, array $datos): array
    {
        $datos['abiertoEn'] ??= now();
        $datos['estado'] = 'abierto';

        if (! empty($datos['doctorId'])) {
            $datos['asignadoEn'] = now();
            $datos['estado'] = 'en_proceso';
        }

        return $datos;
    }

    /** El estado se mueve por acciones explicitas, no por PATCH libre. */
    protected function antesDeActualizar(Request $request, Model $registro, array $datos): array
    {
        unset($datos['estado'], $datos['abiertoEn'], $datos['asignadoEn'], $datos['cerradoEn']);

        return $datos;
    }

    public function asignar(Request $request, int $id): JsonResponse
    {
        $this->autorizarEscritura($request);

        $caso = $this->consulta($request)->findOrFail($id);

        $datos = $request->validate([
            'doctorId' => ['nullable', 'exists:doctores,id'],
        ]);

        // Un doctor sin doctorId explicito se autoasigna el caso.
        $doctorId = $datos['doctorId'] ?? Alcance::doctor($request->user())?->id;

        abort_if($doctorId === null, 422, 'No se pudo determinar el doctor a asignar.');
        abort_if($caso->estado === 'cerrado', 409, 'El caso ya esta cerrado.');

        $antes = $caso->toArray();

        $caso->update([
            'doctorId' => $doctorId,
            'asignadoEn' => now(),
            'estado' => 'en_proceso',
        ]);

        $this->auditar($request, 'asignar', $caso, $antes, $caso->toArray());

        return response()->json($caso->fresh($this->with));
    }

    public function cerrar(Request $request, int $id): JsonResponse
    {
        $this->autorizarEscritura($request);

        $caso = $this->consulta($request)->findOrFail($id);

        abort_if($caso->estado === 'cerrado', 409, 'El caso ya esta cerrado.');

        $antes = $caso->toArray();

        $caso->update([
            'estado' => 'cerrado',
            'cerradoEn' => now(),
        ]);

        $this->auditar($request, 'cerrar', $caso, $antes, $caso->toArray());

        return response()->json($caso->fresh($this->with));
    }
}
