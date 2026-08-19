<?php

// Generado por `php artisan openapi:crud`. No editar a mano.

namespace App\OpenApi\Crud;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Notificacion', description: 'Lectura: admin, doctor, paciente. Escritura: admin.')]
#[OA\Schema(
    schema: 'Notificacion',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', readOnly: true),
        new OA\Property(property: 'usuarioId', type: 'integer'),
        new OA\Property(property: 'tipo', type: 'string', maxLength: 100),
        new OA\Property(property: 'titulo', type: 'string', maxLength: 255),
        new OA\Property(property: 'cuerpo', type: 'string', nullable: true),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
        new OA\Property(property: 'enviadaEn', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
    ],
)]
#[OA\Schema(
    schema: 'NotificacionCrear',
    type: 'object',
    required: ['usuarioId', 'tipo', 'titulo'],
    properties: [
        new OA\Property(property: 'usuarioId', type: 'integer'),
        new OA\Property(property: 'tipo', type: 'string', maxLength: 100),
        new OA\Property(property: 'titulo', type: 'string', maxLength: 255),
        new OA\Property(property: 'cuerpo', type: 'string', nullable: true),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
        new OA\Property(property: 'enviadaEn', type: 'string', format: 'date', nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'NotificacionActualizar',
    type: 'object',
    properties: [
        new OA\Property(property: 'usuarioId', type: 'integer'),
        new OA\Property(property: 'tipo', type: 'string', maxLength: 100),
        new OA\Property(property: 'titulo', type: 'string', maxLength: 255),
        new OA\Property(property: 'cuerpo', type: 'string', nullable: true),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
        new OA\Property(property: 'enviadaEn', type: 'string', format: 'date', nullable: true),
    ],
)]
final class NotificacionDocs
{
    #[OA\Get(
        path: '/notificaciones',
        tags: ['Notificacion'],
        summary: 'Listar Notificacion',
        description: 'Paginado. Filtra por columnas exactas via query string y por rango de creacion (desde/hasta). Solo devuelve lo que esta dentro del alcance del usuario.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/pagina'),
            new OA\Parameter(ref: '#/components/parameters/porPagina'),
            new OA\Parameter(ref: '#/components/parameters/desde'),
            new OA\Parameter(ref: '#/components/parameters/hasta'),
            new OA\Parameter(name: 'orden', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id', 'created_at']), description: 'Columna de orden'),
            new OA\Parameter(ref: '#/components/parameters/direccion'),
            new OA\Parameter(name: 'tipo', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 100), description: 'Filtro exacto por tipo'),
            new OA\Parameter(name: 'usuarioId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por usuarioId'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pagina de Notificacion', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Paginado'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Notificacion'))])])),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
        ],
    )]
    public function getListar(): void {}

    #[OA\Post(
        path: '/notificaciones',
        tags: ['Notificacion'],
        summary: 'Crear Notificacion',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/NotificacionCrear')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/Notificacion')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function postCrear(): void {}

    #[OA\Get(
        path: '/notificaciones/{id}',
        tags: ['Notificacion'],
        summary: 'Ver Notificacion',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Notificacion', content: new OA\JsonContent(ref: '#/components/schemas/Notificacion')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    public function getVer(): void {}

    #[OA\Put(
        path: '/notificaciones/{id}',
        tags: ['Notificacion'],
        summary: 'Actualizar Notificacion',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/NotificacionActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Notificacion')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function putActualizar(): void {}

    #[OA\Patch(
        path: '/notificaciones/{id}',
        tags: ['Notificacion'],
        summary: 'Actualizar Notificacion',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/NotificacionActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Notificacion')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function patchActualizar(): void {}

    #[OA\Delete(
        path: '/notificaciones/{id}',
        tags: ['Notificacion'],
        summary: 'Eliminar Notificacion',
        description: 'Los modelos clinicos usan borrado logico: el dato no se pierde.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 204, description: 'Eliminado'),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflicto'),
        ],
    )]
    public function deleteEliminar(): void {}
}
