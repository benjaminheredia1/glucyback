<?php

namespace App\Http\Controllers;

use App\Models\Archivo;
use App\Models\EstudioMedico;
use App\Models\User;
use App\Support\Alcance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstudioMedicoController extends BaseCrudController
{
    protected string $modelo = EstudioMedico::class;

    protected array $rolesEscritura = [User::ROL_ADMIN, User::ROL_DOCTOR, User::ROL_PACIENTE];

    // paciente.usuario permite que el portal medico muestre de quien es cada
    // estudio sin una peticion extra; el alcance ya acota a la clinica.
    protected array $with = ['tipoEstudio', 'archivo', 'paciente.usuario'];

    protected ?string $columnaPaciente = 'pacienteId';

    protected bool $forzarPacientePropio = true;

    protected array $filtrables = ['pacienteId', 'tipoEstudioId', 'estado', 'origen'];

    protected array $ordenables = ['id', 'fecha', 'created_at'];

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'tipoEstudioId' => [$req, 'exists:tipo_estudios,id'],
            'pacienteId' => [$req, 'exists:pacientes,id'],
            'archivoId' => ['nullable', 'exists:archivos,id'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'fecha' => [$req, 'date'],
            'valor' => ['nullable', 'numeric'],
            'unidad' => ['nullable', 'string', 'max:50'],
            'origen' => ['sometimes', 'in:carga,laboratorio'],
        ];
    }

    /**
     * Un estudio rechazado se vuelve a subir como intento siguiente. El estado y
     * el motivo los fija el doctor en `validar()`, nunca el paciente.
     */
    protected function antesDeCrear(Request $request, array $datos): array
    {
        // El archivoId llega del cliente: hay que comprobar que sea suyo y no de
        // otro paciente. `exists:` solo garantiza que la fila existe.
        Archivo::exigirVisible($datos['archivoId'] ?? null, $request->user());

        $datos['estado'] = 'pendiente';
        $datos['intento'] = EstudioMedico::withTrashed()
            ->where('pacienteId', $datos['pacienteId'])
            ->where('tipoEstudioId', $datos['tipoEstudioId'])
            ->count() + 1;

        return $datos;
    }

    protected function antesDeActualizar(Request $request, Model $registro, array $datos): array
    {
        Archivo::exigirVisible($datos['archivoId'] ?? null, $request->user());

        // El veredicto lo fija el doctor en `validarEstudio()`, nunca un PATCH.
        unset($datos['estado'], $datos['motivoRechazo'], $datos['validadoPor'], $datos['validadoEn'], $datos['intento']);

        return $datos;
    }

    public function validarEstudio(Request $request, int $id): JsonResponse
    {
        $this->autorizar($request);

        $estudio = $this->consulta($request)->findOrFail($id);

        $datos = $request->validate([
            'estado' => ['required', 'in:en_revision,aprobado,rechazado'],
            'motivoRechazo' => ['required_if:estado,rechazado', 'nullable', 'string', 'max:255'],
        ]);

        $doctor = Alcance::doctor($request->user());

        $antes = $estudio->toArray();

        $estudio->update([
            'estado' => $datos['estado'],
            'motivoRechazo' => $datos['estado'] === 'rechazado' ? $datos['motivoRechazo'] : null,
            'validadoPor' => $doctor?->id,
            'validadoEn' => now(),
        ]);

        $this->auditar($request, 'validar', $estudio, $antes, $estudio->toArray());

        return response()->json($estudio->fresh($this->with));
    }

    private function autorizar(Request $request): void
    {
        $usuario = $request->user();

        abort_unless(
            $usuario !== null && ($usuario->esAdmin() || $usuario->esDoctor()),
            403,
            'Solo un doctor puede validar un estudio.'
        );
    }
}
