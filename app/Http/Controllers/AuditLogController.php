<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Solo lectura: la bitacora no se edita ni se borra, es la evidencia.
 */
class AuditLogController extends BaseCrudController
{
    protected string $modelo = AuditLog::class;

    protected array $rolesLectura = [User::ROL_ADMIN];

    protected array $rolesEscritura = [];

    protected array $with = ['usuario'];

    protected array $filtrables = ['entidad', 'entidadId', 'accion', 'usuarioId'];

    protected array $ordenables = ['id', 'created_at'];

    protected function reglas(Request $request, bool $creando): array
    {
        return [];
    }

    public function crear(Request $request): JsonResponse
    {
        abort(405, 'La bitacora no admite escritura manual.');
    }

    public function actualizar(Request $request, int $id): JsonResponse
    {
        abort(405, 'La bitacora es inmutable.');
    }

    public function eliminar(Request $request, int $id): JsonResponse
    {
        abort(405, 'La bitacora no se puede borrar.');
    }
}
