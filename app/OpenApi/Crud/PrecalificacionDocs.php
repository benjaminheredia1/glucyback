<?php

// Generado por `php artisan openapi:crud`. No editar a mano.

namespace App\OpenApi\Crud;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Precalificacion', description: 'Lectura: admin, doctor, paciente. Escritura: admin, doctor, paciente.')]
#[OA\Schema(
    schema: 'Precalificacion',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', readOnly: true),
        new OA\Property(property: 'pacienteId', type: 'integer', nullable: true),
        new OA\Property(property: 'leadEmail', type: 'string', format: 'email', maxLength: 255, nullable: true),
        new OA\Property(property: 'resultado', type: 'string', enum: ['apto', 'no_apto', 'urgente']),
        new OA\Property(property: 'motivo', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'versionCuestionario', type: 'integer', minimum: 1),
        new OA\Property(property: 'respondidoEn', type: 'string', format: 'date'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
    ],
)]
#[OA\Schema(
    schema: 'PrecalificacionCrear',
    type: 'object',
    required: ['resultado'],
    properties: [
        new OA\Property(property: 'pacienteId', type: 'integer', nullable: true),
        new OA\Property(property: 'leadEmail', type: 'string', format: 'email', maxLength: 255, nullable: true),
        new OA\Property(property: 'resultado', type: 'string', enum: ['apto', 'no_apto', 'urgente']),
        new OA\Property(property: 'motivo', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'versionCuestionario', type: 'integer', minimum: 1),
        new OA\Property(property: 'respondidoEn', type: 'string', format: 'date'),
    ],
)]
#[OA\Schema(
    schema: 'PrecalificacionActualizar',
    type: 'object',
    properties: [
        new OA\Property(property: 'pacienteId', type: 'integer', nullable: true),
        new OA\Property(property: 'leadEmail', type: 'string', format: 'email', maxLength: 255, nullable: true),
        new OA\Property(property: 'resultado', type: 'string', enum: ['apto', 'no_apto', 'urgente']),
        new OA\Property(property: 'motivo', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'versionCuestionario', type: 'integer', minimum: 1),
        new OA\Property(property: 'respondidoEn', type: 'string', format: 'date'),
    ],
)]
final class PrecalificacionDocs
{
    #[OA\Get(
        path: '/precalificaciones',
        tags: ['Precalificacion'],
        summary: 'Listar Precalificacion',
        description: 'Paginado. Filtra por columnas exactas via query string y por rango de creacion (desde/hasta). Solo devuelve lo que esta dentro del alcance del usuario.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/pagina'),
            new OA\Parameter(ref: '#/components/parameters/porPagina'),
            new OA\Parameter(ref: '#/components/parameters/desde'),
            new OA\Parameter(ref: '#/components/parameters/hasta'),
            new OA\Parameter(name: 'orden', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id']), description: 'Columna de orden'),
            new OA\Parameter(ref: '#/components/parameters/direccion'),
            new OA\Parameter(name: 'resultado', in: 'query', schema: new OA\Schema(type: 'string', enum: ['apto', 'no_apto', 'urgente']), description: 'Filtro exacto por resultado'),
            new OA\Parameter(name: 'pacienteId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por pacienteId'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pagina de Precalificacion', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Paginado'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Precalificacion'))])])),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
        ],
    )]
    public function getListar(): void {}

    #[OA\Post(
        path: '/precalificaciones',
        tags: ['Precalificacion'],
        summary: 'Crear Precalificacion',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PrecalificacionCrear')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/Precalificacion')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function postCrear(): void {}

    #[OA\Get(
        path: '/precalificaciones/{id}',
        tags: ['Precalificacion'],
        summary: 'Ver Precalificacion',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Precalificacion', content: new OA\JsonContent(ref: '#/components/schemas/Precalificacion')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    public function getVer(): void {}

    #[OA\Put(
        path: '/precalificaciones/{id}',
        tags: ['Precalificacion'],
        summary: 'Actualizar Precalificacion',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PrecalificacionActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Precalificacion')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function putActualizar(): void {}

    #[OA\Patch(
        path: '/precalificaciones/{id}',
        tags: ['Precalificacion'],
        summary: 'Actualizar Precalificacion',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PrecalificacionActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Precalificacion')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function patchActualizar(): void {}

    #[OA\Delete(
        path: '/precalificaciones/{id}',
        tags: ['Precalificacion'],
        summary: 'Eliminar Precalificacion',
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
