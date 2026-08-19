<?php

// Generado por `php artisan openapi:crud`. No editar a mano.

namespace App\OpenApi\Crud;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Suscripcion', description: 'Lectura: admin, doctor, paciente. Escritura: admin.')]
#[OA\Schema(
    schema: 'Suscripcion',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', readOnly: true),
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'planId', type: 'integer'),
        new OA\Property(property: 'licenciaId', type: 'integer', nullable: true),
        new OA\Property(property: 'estado', type: 'string', enum: ['prueba', 'activa', 'vencida', 'cancelada']),
        new OA\Property(property: 'inicio', type: 'string', format: 'date'),
        new OA\Property(property: 'fin', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'proximoCobro', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'consultasUsadas', type: 'integer', minimum: 0),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
    ],
)]
#[OA\Schema(
    schema: 'SuscripcionCrear',
    type: 'object',
    required: ['pacienteId', 'planId'],
    properties: [
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'planId', type: 'integer'),
        new OA\Property(property: 'licenciaId', type: 'integer', nullable: true),
        new OA\Property(property: 'estado', type: 'string', enum: ['prueba', 'activa', 'vencida', 'cancelada']),
        new OA\Property(property: 'inicio', type: 'string', format: 'date'),
        new OA\Property(property: 'fin', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'proximoCobro', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'consultasUsadas', type: 'integer', minimum: 0),
    ],
)]
#[OA\Schema(
    schema: 'SuscripcionActualizar',
    type: 'object',
    properties: [
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'planId', type: 'integer'),
        new OA\Property(property: 'licenciaId', type: 'integer', nullable: true),
        new OA\Property(property: 'estado', type: 'string', enum: ['prueba', 'activa', 'vencida', 'cancelada']),
        new OA\Property(property: 'inicio', type: 'string', format: 'date'),
        new OA\Property(property: 'fin', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'proximoCobro', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'consultasUsadas', type: 'integer', minimum: 0),
    ],
)]
final class SuscripcionDocs
{
    #[OA\Get(
        path: '/suscripciones',
        tags: ['Suscripcion'],
        summary: 'Listar Suscripcion',
        description: 'Paginado. Filtra por columnas exactas via query string y por rango de creacion (desde/hasta). Solo devuelve lo que esta dentro del alcance del usuario.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/pagina'),
            new OA\Parameter(ref: '#/components/parameters/porPagina'),
            new OA\Parameter(ref: '#/components/parameters/desde'),
            new OA\Parameter(ref: '#/components/parameters/hasta'),
            new OA\Parameter(name: 'orden', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id', 'inicio', 'proximoCobro']), description: 'Columna de orden'),
            new OA\Parameter(ref: '#/components/parameters/direccion'),
            new OA\Parameter(name: 'pacienteId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por pacienteId'),
            new OA\Parameter(name: 'planId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por planId'),
            new OA\Parameter(name: 'licenciaId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por licenciaId'),
            new OA\Parameter(name: 'estado', in: 'query', schema: new OA\Schema(type: 'string', enum: ['prueba', 'activa', 'vencida', 'cancelada']), description: 'Filtro exacto por estado'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pagina de Suscripcion', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Paginado'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Suscripcion'))])])),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
        ],
    )]
    public function getListar(): void {}

    #[OA\Post(
        path: '/suscripciones',
        tags: ['Suscripcion'],
        summary: 'Crear Suscripcion',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/SuscripcionCrear')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/Suscripcion')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function postCrear(): void {}

    #[OA\Get(
        path: '/suscripciones/{id}',
        tags: ['Suscripcion'],
        summary: 'Ver Suscripcion',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Suscripcion', content: new OA\JsonContent(ref: '#/components/schemas/Suscripcion')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    public function getVer(): void {}

    #[OA\Put(
        path: '/suscripciones/{id}',
        tags: ['Suscripcion'],
        summary: 'Actualizar Suscripcion',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/SuscripcionActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Suscripcion')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function putActualizar(): void {}

    #[OA\Patch(
        path: '/suscripciones/{id}',
        tags: ['Suscripcion'],
        summary: 'Actualizar Suscripcion',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/SuscripcionActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Suscripcion')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function patchActualizar(): void {}

    #[OA\Delete(
        path: '/suscripciones/{id}',
        tags: ['Suscripcion'],
        summary: 'Eliminar Suscripcion',
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
