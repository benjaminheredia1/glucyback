<?php

namespace App\Ai\Agents;

use App\Models\TipoEstudio;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Revisa el archivo que sube un paciente y decide si es un estudio medico
 * legible. Si lo es, extrae cada resultado que aparezca (uno, dos o cinco) con
 * el nombre exacto del catalogo de TipoEstudio para que el backend los apruebe
 * sin revision manual. Ver App\Support\ValidacionEstudiosIa.
 */
class ValidadorEstudios implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return view('prompts.validador-estudios', [
            'tipos' => TipoEstudio::orderBy('orden')->get(['nombre', 'unidad', 'rangoMin', 'rangoMax']),
        ])->render();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'esEstudioValido' => $schema->boolean()->required(),
            'motivo' => $schema->string()->nullable(),
            'estudiosDetectados' => $schema->array()->items(
                $schema->object([
                    'tipoEstudio' => $schema->string()->required(),
                    'valor' => $schema->number()->required(),
                    'unidad' => $schema->string()->nullable(),
                    'fecha' => $schema->string()->nullable(),
                ])
            )->required(),
        ];
    }
}
