<?php

namespace App\Http\Controllers;

use App\Models\PreguntaPrecalificacion;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PreguntaPrecalificacionController extends BaseCrudController
{
    protected string $modelo = PreguntaPrecalificacion::class;

    protected array $rolesEscritura = [User::ROL_ADMIN];

    protected array $filtrables = ['activa', 'version', 'esUrgente'];

    protected array $ordenables = ['id', 'orden', 'version'];

    protected string $ordenPorDefecto = 'orden';

    protected string $direccionPorDefecto = 'asc';

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'codigo' => [$req, 'string', 'max:50'],
            'texto' => [$req, 'string'],
            'respuestaAlarma' => [$req, 'in:si,no'],
            'esUrgente' => ['sometimes', 'boolean'],
            'motivo' => [$req, 'string', 'max:255'],
            'orden' => ['sometimes', 'integer', 'min:0'],
            'version' => ['sometimes', 'integer', 'min:1'],
            'activa' => ['sometimes', 'boolean'],
        ];
    }

    #[OA\Get(
        path: '/precalificacion/preguntas',
        tags: ['Precalificacion'],
        summary: 'Cuestionario publico del filtro clinico',
        description: 'Preguntas activas ordenadas. No expone la respuesta de alarma ni el motivo: el veredicto lo calcula el backend. Limite: 30 por minuto.',
        responses: [
            new OA\Response(response: 200, description: 'Preguntas', content: new OA\JsonContent(type: 'array', items: new OA\Items(properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'codigo', type: 'string'),
                new OA\Property(property: 'texto', type: 'string'),
                new OA\Property(property: 'orden', type: 'integer'),
                new OA\Property(property: 'version', type: 'integer'),
            ]))),
        ],
    )]
    /**
     * Cuestionario para el filtro clinico, que corre antes de crear la cuenta.
     * No expone `respuestaAlarma` ni `motivo`: el veredicto lo calcula el backend.
     */
    public function publicas(): JsonResponse
    {
        $preguntas = PreguntaPrecalificacion::where('activa', true)
            ->orderBy('orden')
            ->get(['id', 'codigo', 'texto', 'orden', 'version']);

        return response()->json($preguntas);
    }
}
