<?php

// Generado por `php artisan openapi:crud`. No editar a mano.

namespace App\OpenApi\Crud;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Ciclo', description: 'Lectura: admin, doctor, paciente. Escritura: admin, doctor.')]
#[OA\Schema(
    schema: 'Ciclo',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', readOnly: true),
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'numero', type: 'integer', minimum: 1),
        new OA\Property(property: 'inicio', type: 'string', format: 'date'),
        new OA\Property(property: 'fin', type: 'string', format: 'date'),
        new OA\Property(property: 'medicionesRequeridas', type: 'integer', minimum: 1),
        new OA\Property(property: 'estado', type: 'string', enum: ['activo', 'completo', 'vencido']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
    ],
)]
#[OA\Schema(
    schema: 'CicloCrear',
    type: 'object',
    required: ['pacienteId', 'inicio', 'fin'],
    properties: [
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'numero', type: 'integer', minimum: 1),
        new OA\Property(property: 'inicio', type: 'string', format: 'date'),
        new OA\Property(property: 'fin', type: 'string', format: 'date'),
        new OA\Property(property: 'medicionesRequeridas', type: 'integer', minimum: 1),
        new OA\Property(property: 'estado', type: 'string', enum: ['activo', 'completo', 'vencido']),
    ],
)]
#[OA\Schema(
    schema: 'CicloActualizar',
    type: 'object',
    properties: [
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'numero', type: 'integer', minimum: 1),
        new OA\Property(property: 'inicio', type: 'string', format: 'date'),
        new OA\Property(property: 'fin', type: 'string', format: 'date'),
        new OA\Property(property: 'medicionesRequeridas', type: 'integer', minimum: 1),
        new OA\Property(property: 'estado', type: 'string', enum: ['activo', 'completo', 'vencido']),
    ],
)]
final class CicloDocs
{
    #[OA\Get(
        path: '/ciclos',
        tags: ['Ciclo'],
        summary: 'Listar Ciclo',
        description: 'Paginado. Filtra por columnas exactas via query string y por rango de creacion (desde/hasta). Solo devuelve lo que esta dentro del alcance del usuario.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/pagina'),
            new OA\Parameter(ref: '#/components/parameters/porPagina'),
            new OA\Parameter(ref: '#/components/parameters/desde'),
            new OA\Parameter(ref: '#/components/parameters/hasta'),
            new OA\Parameter(name: 'orden', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id', 'numero', 'inicio']), description: 'Columna de orden'),
            new OA\Parameter(ref: '#/components/parameters/direccion'),
            new OA\Parameter(name: 'pacienteId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por pacienteId'),
            new OA\Parameter(name: 'estado', in: 'query', schema: new OA\Schema(type: 'string', enum: ['activo', 'completo', 'vencido']), description: 'Filtro exacto por estado'),
            new OA\Parameter(name: 'numero', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1), description: 'Filtro exacto por numero'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pagina de Ciclo', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Paginado'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Ciclo'))])])),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
        ],
    )]
    public function getListar(): void {}

    #[OA\Post(
        path: '/ciclos',
        tags: ['Ciclo'],
        summary: 'Crear Ciclo',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CicloCrear')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/Ciclo')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function postCrear(): void {}

    #[OA\Get(
        path: '/ciclos/{id}',
        tags: ['Ciclo'],
        summary: 'Ver Ciclo',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Ciclo', content: new OA\JsonContent(ref: '#/components/schemas/Ciclo')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    public function getVer(): void {}

    #[OA\Put(
        path: '/ciclos/{id}',
        tags: ['Ciclo'],
        summary: 'Actualizar Ciclo',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CicloActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Ciclo')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function putActualizar(): void {}

    #[OA\Patch(
        path: '/ciclos/{id}',
        tags: ['Ciclo'],
        summary: 'Actualizar Ciclo',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CicloActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Ciclo')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function patchActualizar(): void {}

    #[OA\Delete(
        path: '/ciclos/{id}',
        tags: ['Ciclo'],
        summary: 'Eliminar Ciclo',
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
