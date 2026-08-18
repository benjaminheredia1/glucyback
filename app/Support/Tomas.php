<?php

namespace App\Support;

use App\Models\PacienteMedicamento;
use App\Models\Toma;
use Carbon\CarbonImmutable;

/**
 * Genera las tomas del dia de un paciente a partir de sus medicamentos con
 * horarios. Se llama al consultar `GET /tomas?dia`, no desde un job: la app
 * pide "hoy" y las tomas aparecen. Idempotente (firstOrCreate + indice unico).
 */
class Tomas
{
    public static function materializar(int $pacienteId, CarbonImmutable $dia, string $zona): void
    {
        // El dia se interpreta en la zona del paciente: 08:00 en La Paz son
        // las 12:00 UTC, y asi se guarda `programadaEn`.
        $inicioDia = $dia->setTimezone($zona)->startOfDay();
        $fecha = $inicioDia->toDateString();

        $medicamentos = PacienteMedicamento::query()
            ->where('pacienteId', $pacienteId)
            ->where('activo', true)
            ->whereNotNull('horarios')
            ->whereDate('fechaInicio', '<=', $fecha)
            ->where(fn ($q) => $q->whereNull('fechaFin')->orWhereDate('fechaFin', '>=', $fecha))
            ->get();

        foreach ($medicamentos as $medicamento) {
            foreach ($medicamento->horarios ?? [] as $hora) {
                [$h, $m] = array_map('intval', explode(':', $hora));
                $programadaEn = $inicioDia->setTime($h, $m)->utc();

                Toma::firstOrCreate(
                    ['pacienteMedicamentoId' => $medicamento->id, 'programadaEn' => $programadaEn],
                    ['estado' => 'pendiente'],
                );
            }
        }
    }
}
