<?php

namespace App\Http\Controllers;

use App\Models\PrecalificacionRespuesta;
use App\Models\User;
use Illuminate\Http\Request;

class PrecalificacionRespuestaController extends BaseCrudController
{
    protected string $modelo = PrecalificacionRespuesta::class;

    protected array $rolesLectura = [User::ROL_ADMIN, User::ROL_DOCTOR];

    protected array $rolesEscritura = [User::ROL_ADMIN];

    protected array $with = ['pregunta'];

    protected ?array $pacienteViaRelacion = ['relacion' => 'precalificacion', 'columna' => 'pacienteId'];

    protected array $filtrables = ['precalificacionId', 'preguntaId', 'respuesta'];

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'precalificacionId' => [$req, 'exists:precalificaciones,id'],
            'preguntaId' => [$req, 'exists:preguntas_precalificacion,id'],
            'respuesta' => [$req, 'in:si,no'],
        ];
    }
}
