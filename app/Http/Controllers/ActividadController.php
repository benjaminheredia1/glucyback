<?php

namespace App\Http\Controllers;

use App\Models\Medicion;
use App\Models\Toma;
use App\Support\Alcance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Historial del paciente para la pestaña "Actividad" de la app: tomas ya
 * marcadas (tomada/omitida) y mediciones de glucosa, mezcladas y ordenadas de
 * la mas reciente a la mas antigua. Dos consultas acotadas y paginacion en
 * memoria: el volumen por paciente es pequeño (unas pocas filas al dia).
 */
class ActividadController extends Controller
{
    public function listar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'pacienteId' => ['nullable', 'integer'],
            'porPagina' => ['nullable', 'integer', 'min:1', 'max:100'],
            'pagina' => ['nullable', 'integer', 'min:1'],
        ]);

        $porPagina = (int) ($datos['porPagina'] ?? 30);
        $pagina = (int) ($datos['pagina'] ?? 1);

        $pacienteId = Alcance::pacienteObjetivo(
            $request->user(),
            isset($datos['pacienteId']) ? (int) $datos['pacienteId'] : null,
        );

        if ($pacienteId === null) {
            return $this->respuesta(collect(), $pagina, $porPagina);
        }

        $desde = $datos['desde'] ?? null;

        $tomas = Toma::query()
            ->with('pacienteMedicamento.medicamento')
            ->whereHas('pacienteMedicamento', fn ($q) => $q->where('pacienteId', $pacienteId))
            ->where('estado', '!=', 'pendiente')
            ->when($desde, fn ($q) => $q->whereDate('programadaEn', '>=', $desde))
            ->get()
            ->map(fn (Toma $toma) => [
                'tipo' => 'toma',
                // Una toma omitida no tiene `tomadaEn`: cuenta desde que se marco.
                'en' => ($toma->tomadaEn ?? $toma->updated_at)?->toJSON(),
                'tomaId' => $toma->id,
                'estado' => $toma->estado,
                'medicamento' => $toma->pacienteMedicamento?->medicamento?->nombre,
                'dosis' => $toma->pacienteMedicamento?->dosis,
                'programadaEn' => $toma->programadaEn?->toJSON(),
            ]);

        $mediciones = Medicion::query()
            ->where('pacienteId', $pacienteId)
            ->when($desde, fn ($q) => $q->whereDate('medidoEn', '>=', $desde))
            ->get()
            ->map(fn (Medicion $medicion) => [
                'tipo' => 'medicion',
                'en' => $medicion->medidoEn?->toJSON(),
                'medicionId' => $medicion->id,
                'valor' => $medicion->valor,
                'unidad' => $medicion->unidad,
                'momento' => $medicion->momento,
            ]);

        $todo = $tomas->concat($mediciones)->sortByDesc('en')->values();

        return $this->respuesta($todo, $pagina, $porPagina);
    }

    private function respuesta(Collection $todo, int $pagina, int $porPagina): JsonResponse
    {
        return response()->json([
            'data' => $todo->slice(($pagina - 1) * $porPagina, $porPagina)->values(),
            'pagina' => $pagina,
            'porPagina' => $porPagina,
            'total' => $todo->count(),
        ]);
    }
}
