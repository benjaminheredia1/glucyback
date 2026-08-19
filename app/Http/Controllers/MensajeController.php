<?php

namespace App\Http\Controllers;

use App\Models\Archivo;
use App\Models\Chat;
use App\Models\Mensaje;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class MensajeController extends BaseCrudController
{
    protected string $modelo = Mensaje::class;

    protected array $rolesEscritura = [User::ROL_ADMIN, User::ROL_DOCTOR, User::ROL_PACIENTE];

    protected array $with = ['autor', 'archivo'];

    protected ?array $pacienteViaRelacion = ['relacion' => 'chat', 'columna' => 'pacienteId'];

    protected array $filtrables = ['chatId', 'esIA', 'autorId'];

    protected array $ordenables = ['id', 'created_at'];

    protected string $direccionPorDefecto = 'asc';

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'chatId' => [$req, 'exists:chats,id'],
            'texto' => [$req, 'string'],
            'archivoId' => ['nullable', 'exists:archivos,id'],
            'esIA' => ['sometimes', 'boolean'],
        ];
    }

    protected function antesDeCrear(Request $request, array $datos): array
    {
        $chat = Chat::findOrFail($datos['chatId']);

        abort_unless($chat->estado === 'abierto', 409, 'El chat esta cerrado.');

        Archivo::exigirVisible($datos['archivoId'] ?? null, $request->user());

        $datos['autorId'] = $request->user()->id;
        $datos['pacienteId'] = $chat->pacienteId;
        // Solo el backend marca un mensaje como generado por IA.
        $datos['esIA'] = false;

        return $datos;
    }

    protected function despuesDeCrear(Request $request, Model $registro): void
    {
        Chat::whereKey($registro->chatId)->update(['ultimoMensajeEn' => now()]);
    }

    #[OA\Post(
        path: '/mensajes/{id}/leido',
        tags: ['Mensaje'],
        summary: 'Marcar mensaje como leido',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Mensaje leido', content: new OA\JsonContent(ref: '#/components/schemas/Mensaje')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),

        ],
    )]
    public function marcarLeido(Request $request, int $id): JsonResponse
    {
        $this->autorizarLectura($request);

        $mensaje = $this->consulta($request)->findOrFail($id);

        if ($mensaje->leidoEn === null) {
            $mensaje->update(['leidoEn' => now()]);
        }

        return response()->json($mensaje->fresh($this->with));
    }
}
