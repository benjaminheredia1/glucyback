<?php

// Generado por `php artisan openapi:crud`. No editar a mano.

namespace App\OpenApi\Crud;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Regla Alerta', description: 'Lectura: admin, doctor. Escritura: admin.')]
#[OA\Schema(
    schema: 'ReglaAlerta',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', readOnly: true),
        new OA\Property(property: 'clinicaId', type: 'integer', nullable: true),
        new OA\Property(property: 'momento', type: 'string', enum: ['ayunas', 'preprandial', 'postprandial', 'nocturna', 'cualquiera']),
        new OA\Property(property: 'minimo', type: 'number', nullable: true),
        new OA\Property(property: 'maximo', type: 'number', nullable: true),
        new OA\Property(property: 'severidad', type: 'string', enum: ['critica', 'alta', 'media']),
        new OA\Property(property: 'mensaje', type: 'string', maxLength: 255),
        new OA\Property(property: 'activa', type: 'boolean'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
    ],
)]
#[OA\Schema(
    schema: 'ReglaAlertaCrear',
    type: 'object',
    required: ['severidad', 'mensaje'],
    properties: [
        new OA\Property(property: 'clinicaId', type: 'integer', nullable: true),
        new OA\Property(property: 'momento', type: 'string', enum: ['ayunas', 'preprandial', 'postprandial', 'nocturna', 'cualquiera']),
        new OA\Property(property: 'minimo', type: 'number', nullable: true),
        new OA\Property(property: 'maximo', type: 'number', nullable: true),
        new OA\Property(property: 'severidad', type: 'string', enum: ['critica', 'alta', 'media']),
        new OA\Property(property: 'mensaje', type: 'string', maxLength: 255),
        new OA\Property(property: 'activa', type: 'boolean'),
    ],
)]
#[OA\Schema(
    schema: 'ReglaAlertaActualizar',
    type: 'object',
    properties: [
        new OA\Property(property: 'clinicaId', type: 'integer', nullable: true),
        new OA\Property(property: 'momento', type: 'string', enum: ['ayunas', 'preprandial', 'postprandial', 'nocturna', 'cualquiera']),
        new OA\Property(property: 'minimo', type: 'number', nullable: true),
        new OA\Property(property: 'maximo', type: 'number', nullable: true),
        new OA\Property(property: 'severidad', type: 'string', enum: ['critica', 'alta', 'media']),
        new OA\Property(property: 'mensaje', type: 'string', maxLength: 255),
        new OA\Property(property: 'activa', type: 'boolean'),
    ],
)]
final class ReglaAlertaDocs
{
    #[OA\Get(
        path: '/reglas-alerta',
        tags: ['Regla Alerta'],
        summary: 'Listar Regla Alerta',
        description: 'Paginado. Filtra por columnas exactas via query string y por rango de creacion (desde/hasta). Solo devuelve lo que esta dentro del alcance del usuario.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/pagina'),
            new OA\Parameter(ref: '#/components/parameters/porPagina'),
            new OA\Parameter(ref: '#/components/parameters/desde'),
            new OA\Parameter(ref: '#/components/parameters/hasta'),
            new OA\Parameter(name: 'orden', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id']), description: 'Columna de orden'),
            new OA\Parameter(ref: '#/components/parameters/direccion'),
            new OA\Parameter(name: 'clinicaId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por clinicaId'),
            new OA\Parameter(name: 'momento', in: 'query', schema: new OA\Schema(type: 'string', enum: ['ayunas', 'preprandial', 'postprandial', 'nocturna', 'cualquiera']), description: 'Filtro exacto por momento'),
            new OA\Parameter(name: 'severidad', in: 'query', schema: new OA\Schema(type: 'string', enum: ['critica', 'alta', 'media']), description: 'Filtro exacto por severidad'),
            new OA\Parameter(name: 'activa', in: 'query', schema: new OA\Schema(type: 'boolean'), description: 'Filtro exacto por activa'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pagina de Regla Alerta', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Paginado'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ReglaAlerta'))])])),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
        ],
    )]
    public function getListar(): void {}

    #[OA\Post(
        path: '/reglas-alerta',
        tags: ['Regla Alerta'],
        summary: 'Crear Regla Alerta',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ReglaAlertaCrear')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/ReglaAlerta')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function postCrear(): void {}

    #[OA\Get(
        path: '/reglas-alerta/{id}',
        tags: ['Regla Alerta'],
        summary: 'Ver Regla Alerta',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Regla Alerta', content: new OA\JsonContent(ref: '#/components/schemas/ReglaAlerta')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    public function getVer(): void {}

    #[OA\Put(
        path: '/reglas-alerta/{id}',
        tags: ['Regla Alerta'],
        summary: 'Actualizar Regla Alerta',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ReglaAlertaActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/ReglaAlerta')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function putActualizar(): void {}

    #[OA\Patch(
        path: '/reglas-alerta/{id}',
        tags: ['Regla Alerta'],
        summary: 'Actualizar Regla Alerta',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ReglaAlertaActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/ReglaAlerta')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function patchActualizar(): void {}

    #[OA\Delete(
        path: '/reglas-alerta/{id}',
        tags: ['Regla Alerta'],
        summary: 'Eliminar Regla Alerta',
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
