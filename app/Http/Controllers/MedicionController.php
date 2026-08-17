<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Caso;
use App\Models\Ciclo;
use App\Models\Medicion;
use App\Models\Paciente;
use App\Models\ReglaAlerta;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class MedicionController extends BaseCrudController
{
    protected string $modelo = Medicion::class;

    protected array $rolesEscritura = [User::ROL_ADMIN, User::ROL_DOCTOR, User::ROL_PACIENTE];

    protected ?string $columnaPaciente = 'pacienteId';

    protected bool $forzarPacientePropio = true;

    protected array $filtrables = ['pacienteId', 'cicloId', 'momento', 'fuente'];

    protected array $ordenables = ['id', 'medidoEn', 'valor'];

    protected string $ordenPorDefecto = 'medidoEn';

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'pacienteId' => [$req, 'exists:pacientes,id'],
            'cicloId' => ['nullable', 'exists:ciclos,id'],
            'valor' => [$req, 'numeric', 'between:10,900'],
            'unidad' => ['sometimes', 'in:mg/dL,mmol/L'],
            'momento' => [$req, 'in:ayunas,preprandial,postprandial,nocturna'],
            'fuente' => ['sometimes', 'in:manual,dispositivo'],
            'medidoEn' => ['sometimes', 'date', 'before_or_equal:now'],
            'nota' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function antesDeCrear(Request $request, array $datos): array
    {
        $datos['medidoEn'] ??= now();
        // La medicion entra al ciclo activo del paciente si no se indica otro.
        $datos['cicloId'] ??= Ciclo::where('pacienteId', $datos['pacienteId'])
            ->where('estado', 'activo')
            ->value('id');

        return $datos;
    }

    protected function despuesDeCrear(Request $request, Model $registro): void
    {
        $this->avanzarCiclo($registro);
        $this->evaluarAlertas($registro);
    }

    private function avanzarCiclo(Medicion $medicion): void
    {
        if ($medicion->cicloId === null) {
            return;
        }

        $ciclo = Ciclo::find($medicion->cicloId);

        if ($ciclo === null) {
            return;
        }

        $ciclo->increment('medicionesRegistradas');
        $ciclo->refresh();

        if ($ciclo->estado === 'activo' && $ciclo->estaCompleto()) {
            $ciclo->update(['estado' => 'completo']);

            // Ciclo completo abre el caso de ajuste que vera el doctor.
            Caso::create([
                'pacienteId' => $ciclo->pacienteId,
                'cicloId' => $ciclo->id,
                'tipo' => 'ajuste_ciclo',
                'urgencia' => 'pendiente',
                'estado' => 'abierto',
                'titulo' => "Ajuste de ciclo #{$ciclo->numero}",
                'abiertoEn' => now(),
            ]);
        }
    }

    private function evaluarAlertas(Medicion $medicion): void
    {
        $clinicaId = Paciente::where('id', $medicion->pacienteId)->value('clinicaId');

        $reglas = ReglaAlerta::where('activa', true)
            ->where(function ($q) use ($clinicaId) {
                $q->whereNull('clinicaId')->orWhere('clinicaId', $clinicaId);
            })
            ->get();

        foreach ($reglas as $regla) {
            if (! $regla->aplicaA($medicion)) {
                continue;
            }

            $caso = null;

            if ($regla->severidad === 'critica') {
                $caso = Caso::create([
                    'pacienteId' => $medicion->pacienteId,
                    'cicloId' => $medicion->cicloId,
                    'tipo' => 'alerta',
                    'urgencia' => 'urgente',
                    'estado' => 'abierto',
                    'titulo' => "Valor critico {$medicion->valor} {$medicion->unidad}",
                    'abiertoEn' => now(),
                ]);
            }

            Alerta::create([
                'pacienteId' => $medicion->pacienteId,
                'medicionId' => $medicion->id,
                'reglaId' => $regla->id,
                'casoId' => $caso?->id,
                'tipo' => 'valor_critico',
                'severidad' => $regla->severidad,
                'mensaje' => $regla->mensaje,
                'estado' => 'abierta',
            ]);

            // Una sola alerta por medicion: gana la regla mas especifica cargada primero.
            break;
        }
    }
}
