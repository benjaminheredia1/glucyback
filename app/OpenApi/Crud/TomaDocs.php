<?php

// Generado por `php artisan openapi:crud`. No editar a mano.

namespace App\OpenApi\Crud;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Toma', description: 'Lectura: admin, doctor, paciente. Escritura: admin, doctor, paciente.')]
#[OA\Schema(
    schema: 'Toma',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', readOnly: true),
        new OA\Property(property: 'pacienteMedicamentoId', type: 'integer'),
        new OA\Property(property: 'programadaEn', type: 'string', format: 'date'),
        new OA\Property(property: 'tomadaEn', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'estado', type: 'string', enum: ['pendiente', 'tomada', 'omitida']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
    ],
)]
#[OA\Schema(
    schema: 'TomaCrear',
    type: 'object',
    required: ['pacienteMedicamentoId', 'programadaEn'],
    properties: [
        new OA\Property(property: 'pacienteMedicamentoId', type: 'integer'),
        new OA\Property(property: 'programadaEn', type: 'string', format: 'date'),
        new OA\Property(property: 'tomadaEn', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'estado', type: 'string', enum: ['pendiente', 'tomada', 'omitida']),
    ],
)]
#[OA\Schema(
    schema: 'TomaActualizar',
    type: 'object',
    properties: [
        new OA\Property(property: 'pacienteMedicamentoId', type: 'integer'),
        new OA\Property(property: 'programadaEn', type: 'string', format: 'date'),
        new OA\Property(property: 'tomadaEn', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'estado', type: 'string', enum: ['pendiente', 'tomada', 'omitida']),
    ],
)]
final class TomaDocs
{
    #[OA\Get(
        path: '/tomas',
        tags: ['Toma'],
        summary: 'Listar Toma',
        description: 'Paginado. Filtra por columnas exactas via query string y por rango de creacion (desde/hasta). Solo devuelve lo que esta dentro del alcance del usuario.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/pagina'),
            new OA\Parameter(ref: '#/components/parameters/porPagina'),
            new OA\Parameter(ref: '#/components/parameters/desde'),
            new OA\Parameter(ref: '#/components/parameters/hasta'),
            new OA\Parameter(name: 'orden', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id', 'programadaEn']), description: 'Columna de orden'),
            new OA\Parameter(ref: '#/components/parameters/direccion'),
            new OA\Parameter(name: 'pacienteMedicamentoId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por pacienteMedicamentoId'),
            new OA\Parameter(name: 'estado', in: 'query', schema: new OA\Schema(type: 'string', enum: ['pendiente', 'tomada', 'omitida']), description: 'Filtro exacto por estado'),
            new OA\Parameter(name: 'dia', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'), description: 'Dia local (YYYY-MM-DD) del que se quieren las tomas'),
            new OA\Parameter(name: 'zona', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Zona horaria IANA del paciente (ej. America/La_Paz)'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pagina de Toma', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Paginado'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Toma'))])])),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
        ],
    )]
    public function getListar(): void {}

    #[OA\Post(
        path: '/tomas',
        tags: ['Toma'],
        summary: 'Crear Toma',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/TomaCrear')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/Toma')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function postCrear(): void {}

    #[OA\Get(
        path: '/tomas/{id}',
        tags: ['Toma'],
        summary: 'Ver Toma',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Toma', content: new OA\JsonContent(ref: '#/components/schemas/Toma')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    public function getVer(): void {}

    #[OA\Put(
        path: '/tomas/{id}',
        tags: ['Toma'],
        summary: 'Actualizar Toma',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/TomaActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Toma')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function putActualizar(): void {}

    #[OA\Patch(
        path: '/tomas/{id}',
        tags: ['Toma'],
        summary: 'Actualizar Toma',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/TomaActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Toma')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function patchActualizar(): void {}

    #[OA\Delete(
        path: '/tomas/{id}',
        tags: ['Toma'],
        summary: 'Eliminar Toma',
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
