<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class NotificacionController extends BaseCrudController
{
    protected string $modelo = Notificacion::class;

    protected array $rolesEscritura = [User::ROL_ADMIN];

    protected array $filtrables = ['tipo', 'usuarioId'];

    protected array $ordenables = ['id', 'created_at'];

    /** Cada usuario ve solo su bandeja. */
    protected function aplicarAlcance(Builder $consulta, ?User $usuario): void
    {
        if ($usuario === null || $usuario->esAdmin()) {
            return;
        }

        $consulta->where('usuarioId', $usuario->id);
    }

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'usuarioId' => [$req, 'exists:users,id'],
            'tipo' => [$req, 'string', 'max:100'],
            'titulo' => [$req, 'string', 'max:255'],
            'cuerpo' => ['nullable', 'string'],
            'data' => ['nullable', 'array'],
            'enviadaEn' => ['nullable', 'date'],
        ];
    }

    #[OA\Post(
        path: '/notificaciones/{id}/leida',
        tags: ['Notificacion'],
        summary: 'Marcar notificacion como leida',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Notificacion leida', content: new OA\JsonContent(ref: '#/components/schemas/Notificacion')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),

        ],
    )]
    public function marcarLeida(Request $request, int $id): JsonResponse
    {
        $this->autorizarLectura($request);

        $notificacion = $this->consulta($request)->findOrFail($id);

        if ($notificacion->leidaEn === null) {
            $notificacion->update(['leidaEn' => now()]);
        }

        return response()->json($notificacion->fresh());
    }
}
