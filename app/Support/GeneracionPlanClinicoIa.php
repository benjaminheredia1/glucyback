<?php

namespace App\Support;

use App\Ai\Agents\GeneradorPlanClinico;
use App\Models\AuditLog;
use App\Models\Diagnostico;
use App\Models\EstudioMedico;
use App\Models\Paciente;
use App\Models\TipoEstudio;
use App\Models\Tratamiento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Cuando el paciente completa todos sus estudios obligatorios aprobados, la IA
 * redacta un diagnostico y un tratamiento en borrador con aceptadoDoctor=false.
 * El doctor los revisa y acepta (PATCH aceptadoDoctor=true) o los reescribe.
 *
 * Funciona por ciclos: la primera vez genera la version 1; si el doctor pide
 * estudios de nuevo y llegan aprobaciones posteriores al ultimo diagnostico,
 * genera la version siguiente usando el historico completo (estudios y
 * diagnosticos/tratamientos previos). Sin aprobaciones nuevas no duplica.
 * Si la IA falla, no pasa nada: se reintenta sola en la proxima aprobacion.
 */
class GeneracionPlanClinicoIa
{
    /**
     * @return array{diagnostico: Diagnostico, tratamiento: Tratamiento}|null
     */
    public function generarSiCorresponde(Paciente $paciente): ?array
    {
        if (! config('ai.generar_plan_clinico')) {
            return null;
        }

        if (! $this->estudiosCompletos($paciente)) {
            return null;
        }

        $ultimo = Diagnostico::where('pacienteId', $paciente->id)
            ->orderByDesc('id')
            ->first();

        // Nuevo ciclo = hay aprobaciones posteriores al ultimo diagnostico.
        if ($ultimo !== null && ! $this->hayAprobacionesDespuesDe($paciente, $ultimo)) {
            return null;
        }

        $plan = $this->redactar($paciente);

        if ($plan === null) {
            return null;
        }

        $version = ($ultimo->version ?? 0) + 1;

        return DB::transaction(function () use ($paciente, $plan, $version) {
            $diagnostico = Diagnostico::create([
                'pacienteId' => $paciente->id,
                'descripcion' => Str::limit($plan['diagnosticoResumen'], 255, ''),
                'diagnosticoAI' => $plan['diagnosticoDetalle'],
                'estado' => 'borrador',
                'aceptadoDoctor' => false,
                'version' => $version,
            ]);

            $tratamiento = Tratamiento::create([
                'pacienteId' => $paciente->id,
                'descripcion' => Str::limit($plan['tratamientoResumen'], 255, ''),
                'tratamientoAI' => $plan['tratamientoDetalle'],
                'estado' => 'borrador',
                'aceptadoDoctor' => false,
                'version' => $version,
            ]);

            foreach ([$diagnostico, $tratamiento] as $registro) {
                AuditLog::create([
                    'usuarioId' => null, // null = generado por la IA
                    'entidad' => class_basename($registro),
                    'entidadId' => $registro->getKey(),
                    'accion' => 'generar-ia',
                    'antes' => null,
                    'despues' => $registro->toArray(),
                    'ip' => request()->ip(),
                ]);
            }

            return ['diagnostico' => $diagnostico, 'tratamiento' => $tratamiento];
        });
    }

    /** Algun estudio se aprobo despues de creado el ultimo diagnostico. */
    private function hayAprobacionesDespuesDe(Paciente $paciente, Diagnostico $diagnostico): bool
    {
        return EstudioMedico::where('pacienteId', $paciente->id)
            ->where('estado', 'aprobado')
            ->where(function ($q) use ($diagnostico) {
                $q->where('validadoEn', '>', $diagnostico->created_at)
                    ->orWhere(fn ($q2) => $q2->whereNull('validadoEn')->where('updated_at', '>', $diagnostico->created_at));
            })
            ->exists();
    }

    /** Todos los tipos de estudio obligatorios tienen un estudio aprobado. */
    private function estudiosCompletos(Paciente $paciente): bool
    {
        $obligatorios = TipoEstudio::where('esObligatorio', true)->pluck('id');

        if ($obligatorios->isEmpty()) {
            return false;
        }

        $aprobados = EstudioMedico::where('pacienteId', $paciente->id)
            ->where('estado', 'aprobado')
            ->distinct()
            ->pluck('tipoEstudioId');

        return $obligatorios->diff($aprobados)->isEmpty();
    }

    /**
     * @return array{diagnosticoResumen: string, diagnosticoDetalle: string, tratamientoResumen: string, tratamientoDetalle: string}|null
     */
    private function redactar(Paciente $paciente): ?array
    {
        try {
            $respuesta = (new GeneradorPlanClinico($paciente))->prompt(
                'Redacta la propuesta de diagnostico y tratamiento segun tus instrucciones.',
                provider: config('ai.estudios_provider'),
                model: config('ai.estudios_model'),
                timeout: 120,
            );

            $datos = $respuesta->structured ?? [];

            if ($datos === []) {
                $datos = json_decode($respuesta->text, true);
            }

            foreach (['diagnosticoResumen', 'diagnosticoDetalle', 'tratamientoResumen', 'tratamientoDetalle'] as $campo) {
                if (blank($datos[$campo] ?? null)) {
                    return null;
                }
            }

            return $datos;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }
}
