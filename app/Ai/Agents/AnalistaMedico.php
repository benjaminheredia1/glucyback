<?php

namespace App\Ai\Agents;

use Exception;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;
use App\Models\Paciente;
use App\Models\medicamntos_antiguos;

class AnalistaMedico implements Agent, Conversational, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function __construct(public Paciente $paciente)
    {
    }

    public function instructions(int $usuario_id): Stringable|string
    {
        $paciente_lda = $this->paciente->where('id', $usuario_id)->first();
        if(empty($paciente_lda)) {
            throw new Exception("Paciente no encontrado");
        }
        $medicamentosActuales = medicamntos_antiguos::obtenerMedicamentosAntiguos($usuario_id);
        return view('prompts.analistamedico', [
            'edad' => $paciente_lda->fechaNacimiento->age,
            'datosBasales' => $paciente_lda->only([
                'peso', 'talla', 'imc', 'presionArterial',
                'aniosConDiabetes', 'medicacionActual',
            ]),
            'perfilLada' => $paciente_lda->sospechaLada, 
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
