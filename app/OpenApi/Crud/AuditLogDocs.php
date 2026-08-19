<?php

// Generado por `php artisan openapi:crud`. No editar a mano.

namespace App\OpenApi\Crud;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Audit Log', description: 'Lectura: admin. Escritura: .')]
#[OA\Schema(
    schema: 'AuditLog',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', readOnly: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
    ],
)]
#[OA\Schema(
    schema: 'AuditLogCrear',
    type: 'object',
    properties: [
    ],
)]
#[OA\Schema(
    schema: 'AuditLogActualizar',
    type: 'object',
    properties: [
    ],
)]
final class AuditLogDocs
{
    #[OA\Get(
        path: '/audit-logs',
        tags: ['Audit Log'],
        summary: 'Listar Audit Log',
        description: 'Paginado. Filtra por columnas exactas via query string y por rango de creacion (desde/hasta). Solo devuelve lo que esta dentro del alcance del usuario.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/pagina'),
            new OA\Parameter(ref: '#/components/parameters/porPagina'),
            new OA\Parameter(ref: '#/components/parameters/desde'),
            new OA\Parameter(ref: '#/components/parameters/hasta'),
            new OA\Parameter(name: 'orden', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id', 'created_at']), description: 'Columna de orden'),
            new OA\Parameter(ref: '#/components/parameters/direccion'),
            new OA\Parameter(name: 'entidad', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Filtro exacto por entidad'),
            new OA\Parameter(name: 'entidadId', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Filtro exacto por entidadId'),
            new OA\Parameter(name: 'accion', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Filtro exacto por accion'),
            new OA\Parameter(name: 'usuarioId', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Filtro exacto por usuarioId'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pagina de Audit Log', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Paginado'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/AuditLog'))])])),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
        ],
    )]
    public function getListar(): void {}

    #[OA\Get(
        path: '/audit-logs/{id}',
        tags: ['Audit Log'],
        summary: 'Ver Audit Log',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Audit Log', content: new OA\JsonContent(ref: '#/components/schemas/AuditLog')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    public function getVer(): void {}
}
