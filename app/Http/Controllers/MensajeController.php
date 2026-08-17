<?php

namespace App\Http\Controllers;

use App\Models\Archivo;
use App\Models\Chat;
use App\Models\Mensaje;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
