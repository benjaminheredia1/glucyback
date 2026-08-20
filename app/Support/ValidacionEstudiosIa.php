<?php

namespace App\Support;

use App\Ai\Agents\ValidadorEstudios;
use App\Models\Archivo;
use App\Models\AuditLog;
use App\Models\EstudioMedico;
use App\Models\Paciente;
use App\Models\TipoEstudio;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Laravel\Ai\Files\Document;
use Laravel\Ai\Files\Image;
use Throwable;

/**
 * Validacion por IA de la subida de estudios del paciente.
 *
 * `analizar()` manda el archivo al agente ValidadorEstudios antes de guardarlo.
 * `aprobar()` marca los estudios detectados como aprobados (validadoPor null =
 * los valido la IA; el doctor puede revertir con POST /estudios-medicos/{id}/validar).
 *
 * Si el proveedor de IA falla, `analizar()` devuelve null y la subida sigue el
 * flujo manual de siempre: la IA nunca bloquea por estar caida.
 */
class ValidacionEstudiosIa
{
    /**
     * @return array{esEstudioValido: bool, motivo: ?string, estudiosDetectados: array<int, array<string, mixed>>}|null
     */
    public function analizar(UploadedFile $subido): ?array
    {
        try {
            $adjunto = str_starts_with((string) $subido->getMimeType(), 'image/')
                ? Image::fromPath($subido->getRealPath(), $subido->getMimeType())
                : Document::fromPath($subido->getRealPath());

            $respuesta = (new ValidadorEstudios)->prompt(
                'Analiza el archivo adjunto segun tus instrucciones.',
                attachments: [$adjunto],
                provider: config('ai.estudios_provider'),
                model: config('ai.estudios_model'),
                timeout: 120,
            );

            $datos = $respuesta->structured ?? [];

            if ($datos === []) {
                $datos = json_decode($respuesta->text, true);
            }

            if (! is_array($datos) || ! array_key_exists('esEstudioValido', $datos)) {
                return null;
            }

            return [
                'esEstudioValido' => (bool) $datos['esEstudioValido'],
                'motivo' => $datos['motivo'] ?? null,
                'estudiosDetectados' => is_array($datos['estudiosDetectados'] ?? null) ? $datos['estudiosDetectados'] : [],
            ];
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Aprueba en la base los estudios que la IA detecto en el archivo. Si el
     * paciente ya tenia un estudio pendiente o rechazado de ese tipo, se
     * aprueba esa misma fila (es el reintento); si no, se crea una nueva.
     *
     * @param  array<int, array<string, mixed>>  $detectados
     * @return array<int, EstudioMedico>
     */
    public function aprobar(Archivo $archivo, Paciente $paciente, array $detectados): array
    {
        $tipos = TipoEstudio::all()->keyBy(fn (TipoEstudio $t) => mb_strtolower($t->nombre));

        $aprobados = [];

        foreach ($detectados as $detectado) {
            $tipo = $tipos->get(mb_strtolower((string) ($detectado['tipoEstudio'] ?? '')));

            if ($tipo === null || ! is_numeric($detectado['valor'] ?? null)) {
                continue;
            }

            $fecha = rescue(fn () => Carbon::parse($detectado['fecha'])->toDateString(), now()->toDateString(), false);

            $cambios = [
                'archivoId' => $archivo->id,
                'fecha' => $fecha,
                'valor' => $detectado['valor'],
                'unidad' => $detectado['unidad'] ?? $tipo->unidad,
                'estado' => 'aprobado',
                'motivoRechazo' => null,
                'validadoPor' => null,
                'validadoEn' => now(),
            ];

            $existente = EstudioMedico::where('pacienteId', $paciente->id)
                ->where('tipoEstudioId', $tipo->id)
                ->whereIn('estado', ['pendiente', 'rechazado', 'en_revision'])
                ->orderByDesc('intento')
                ->first();

            if ($existente !== null) {
                $antes = $existente->toArray();
                $existente->update($cambios);
                $this->auditar($existente, $antes);
                $aprobados[] = $existente->fresh();

                continue;
            }

            $estudio = EstudioMedico::create($cambios + [
                'tipoEstudioId' => $tipo->id,
                'pacienteId' => $paciente->id,
                'origen' => 'carga',
                'intento' => EstudioMedico::withTrashed()
                    ->where('pacienteId', $paciente->id)
                    ->where('tipoEstudioId', $tipo->id)
                    ->count() + 1,
            ]);

            $this->auditar($estudio, null);
            $aprobados[] = $estudio;
        }

        if ($aprobados !== []) {
            // Si con esto el paciente completo sus obligatorios, la IA deja
            // lista la propuesta de diagnostico y tratamiento para el doctor.
            app(GeneracionPlanClinicoIa::class)->generarSiCorresponde($paciente);
        }

        return $aprobados;
    }

    private function auditar(EstudioMedico $estudio, ?array $antes): void
    {
        AuditLog::create([
            'usuarioId' => null, // null = accion automatica de la IA
            'entidad' => class_basename($estudio),
            'entidadId' => $estudio->getKey(),
            'accion' => 'validar-ia',
            'antes' => $antes,
            'despues' => $estudio->toArray(),
            'ip' => request()->ip(),
        ]);
    }
}
