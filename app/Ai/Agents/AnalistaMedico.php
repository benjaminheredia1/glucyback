<?php

namespace App\Ai\Agents;

use App\Models\medicamntos_antiguos;
use App\Models\Paciente;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

class AnalistaMedico implements Agent, Conversational, HasStructuredOutput, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function __construct(public Paciente $paciente) {}

    public function instructions(): Stringable|string
    {
        // String "Nombre A, Nombre B" con la medicacion previa del paciente.
        $medicamentosAntiguos = medicamntos_antiguos::obtenerMedicamentosAntiguos($this->paciente->id);

        return view('prompts.analistamedico', [
            // Un paciente anonimo del embudo puede no tener fecha todavia.
            'edad' => $this->paciente->fechaNacimiento?->age ?? 'desconocida',
            'datosBasales' => $this->paciente->only([
                'peso', 'talla', 'imc', 'presionArterial', 'aniosConDiabetes',
            ]),
            'medicamentosAntiguos' => $medicamentosAntiguos,
            'perfilLada' => $this->paciente->sospechaLada,
        ])->render();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'estudiosDetectados' => $schema->array()->items(
                $schema->object([
                    'tipoEstudio' => $schema->string()->required(),
                    'valor' => $schema->number()->required(),
                    'unidad' => $schema->string()->required(),
                    'fecha' => $schema->string()->nullable(),
                ])
            )->required(),
            'estudiosFaltantes' => $schema->array()->items($schema->string())->required(),
            'clasificacion' => $schema->string()->enum(['VERDE', 'AMARILLO', 'ROJO'])->required(),
            'razonesClasificacion' => $schema->array()->items($schema->string())->required(),
            'requiereRevisionPrioritaria' => $schema->boolean()->required(),
        ];
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [];
    }
}
