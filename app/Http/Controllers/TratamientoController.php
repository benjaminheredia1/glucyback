<?php

namespace App\Http\Controllers;

use App\Models\Tratamiento;
use App\Models\User;
use App\Support\Alcance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TratamientoController extends BaseCrudController
{
    protected string $modelo = Tratamiento::class;

    protected array $rolesEscritura = [User::ROL_ADMIN, User::ROL_DOCTOR];

    protected array $with = ['doctor.usuario', 'caso', 'ciclo', 'medicamentos.medicamento'];

    protected ?string $columnaPaciente = 'pacienteId';

    protected array $filtrables = ['pacienteId', 'doctorId', 'casoId', 'cicloId', 'estado'];

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'pacienteId' => [$req, 'exists:pacientes,id'],
            'doctorId' => ['nullable', 'exists:doctores,id'],
            'casoId' => ['nullable', 'exists:casos,id'],
            'cicloId' => ['nullable', 'exists:ciclos,id'],
            'descripcion' => [$req, 'string', 'max:255'],
            'tratamientoAI' => ['nullable', 'string'],
            'tratamientoDoctor' => ['nullable', 'string'],
            'notaDoctor' => ['nullable', 'string'],
            'estado' => ['sometimes', 'in:borrador,pendiente_firma'],
        ];
    }

    protected function antesDeActualizar(Request $request, Model $registro, array $datos): array
    {
        abort_if(
            $registro->estaFirmado(),
            409,
            'Un tratamiento firmado no se edita: usa POST /tratamientos/{id}/reemplazar.'
        );

        unset($datos['firmadoEn'], $datos['enviadoEn'], $datos['version'], $datos['reemplazaA']);

        return $datos;
    }

    protected function antesDeEliminar(Request $request, Model $registro): void
    {
        abort_if($registro->estaFirmado(), 409, 'Un tratamiento firmado no se puede eliminar.');
    }

    public function firmar(Request $request, int $id): JsonResponse
    {
        $this->autorizarEscritura($request);

        $tratamiento = $this->consulta($request)->findOrFail($id);

        abort_if($tratamiento->estaFirmado(), 409, 'El tratamiento ya esta firmado.');

        $doctor = Alcance::doctor($request->user());

        abort_if(
            $doctor === null && ! $request->user()->esAdmin(),
            422,
            'Solo un doctor con matricula puede firmar.'
        );

        abort_if(
            blank($tratamiento->tratamientoDoctor),
            422,
            'No se puede firmar un tratamiento sin indicaciones del doctor.'
        );

        $antes = $tratamiento->toArray();

        $tratamiento->update([
            'doctorId' => $doctor?->id ?? $tratamiento->doctorId,
            'estado' => 'firmado',
            'firmadoEn' => now(),
        ]);

        $this->auditar($request, 'firmar', $tratamiento, $antes, $tratamiento->toArray());

        return response()->json($tratamiento->fresh($this->with));
    }

    public function enviar(Request $request, int $id): JsonResponse
    {
        $this->autorizarEscritura($request);

        $tratamiento = $this->consulta($request)->findOrFail($id);

        abort_unless($tratamiento->estado === 'firmado', 409, 'Solo se envia un tratamiento firmado.');

        $antes = $tratamiento->toArray();

        $tratamiento->update(['estado' => 'enviado', 'enviadoEn' => now()]);

        $this->auditar($request, 'enviar', $tratamiento, $antes, $tratamiento->toArray());

        return response()->json($tratamiento->fresh($this->with));
    }

    /**
     * Ajuste de ciclo: emite una version nueva encadenada al tratamiento firmado.
     * El original queda intacto como historial clinico.
     */
    public function reemplazar(Request $request, int $id): JsonResponse
    {
        $this->autorizarEscritura($request);

        $anterior = $this->consulta($request)->findOrFail($id);

        abort_unless($anterior->estaFirmado(), 409, 'Solo se reemplaza un tratamiento firmado.');
        abort_if($anterior->reemplazo()->exists(), 409, 'Este tratamiento ya fue reemplazado.');

        $datos = $request->validate([
            'descripcion' => ['required', 'string', 'max:255'],
            'tratamientoAI' => ['nullable', 'string'],
            'tratamientoDoctor' => ['nullable', 'string'],
            'notaDoctor' => ['nullable', 'string'],
            'cicloId' => ['nullable', 'exists:ciclos,id'],
            'casoId' => ['nullable', 'exists:casos,id'],
        ]);

        $nuevo = DB::transaction(function () use ($request, $anterior, $datos) {
            $nuevo = Tratamiento::create([
                ...$datos,
                'pacienteId' => $anterior->pacienteId,
                'doctorId' => Alcance::doctor($request->user())?->id,
                'estado' => 'borrador',
                'version' => $anterior->version + 1,
                'reemplazaA' => $anterior->id,
            ]);

            $this->auditar($request, 'reemplazar', $nuevo, $anterior->toArray(), $nuevo->toArray());

            return $nuevo;
        });

        return response()->json($nuevo->load($this->with), 201);
    }
}
