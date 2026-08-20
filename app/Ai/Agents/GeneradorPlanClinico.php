<?php

namespace App\Ai\Agents;

use App\Models\Diagnostico;
use App\Models\Paciente;
use App\Models\Tratamiento;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Propone un diagnostico y un tratamiento cuando el paciente completo todos
 * sus estudios obligatorios aprobados. Lo que genera queda en borrador con
 * aceptadoDoctor=false: es una propuesta, el doctor la revisa y la acepta.
 * Ver App\Support\GeneracionPlanClinicoIa.
 */
class GeneradorPlanClinico implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(public Paciente $paciente) {}

    public function instructions(): Stringable|string
    {
        $paciente = $this->paciente;

        return view('prompts.generador-plan-clinico', [
            'edad' => $paciente->fechaNacimiento?->age,
            'tipoDiabetes' => $paciente->tipoDiabetes,
            'perfilLada' => $paciente->sospechaLada,
            'datosBasales' => $paciente->only([
                'peso', 'talla', 'imc', 'presionArterial', 'aniosConDiabetes',
                'alergias', 'comorbilidades', 'tabaquismo',
            ]),
            'estudios' => $paciente->estudiosMedicos()
                ->with('tipoEstudio')
                ->where('estado', 'aprobado')
                ->orderBy('fecha')
                ->get(),
            // Ciclos anteriores: la nueva propuesta debe leerse como evolucion
            // del historico, no como primer contacto.
            'diagnosticosPrevios' => Diagnostico::where('pacienteId', $paciente->id)
                ->orderBy('version')
                ->get(),
            'tratamientosPrevios' => Tratamiento::where('pacienteId', $paciente->id)
                ->orderBy('version')
                ->get(),
        ])->render();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'diagnosticoResumen' => $schema->string()->required(),
            'diagnosticoDetalle' => $schema->string()->required(),
            'tratamientoResumen' => $schema->string()->required(),
            'tratamientoDetalle' => $schema->string()->required(),
        ];
    }
}
