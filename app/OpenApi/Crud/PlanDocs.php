<?php

// Generado por `php artisan openapi:crud`. No editar a mano.

namespace App\OpenApi\Crud;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Plan', description: 'Lectura: admin, doctor, paciente. Escritura: admin.')]
#[OA\Schema(
    schema: 'Plan',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', readOnly: true),
        new OA\Property(property: 'nombre', type: 'string', maxLength: 255),
        new OA\Property(property: 'descripcion', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'ambito', type: 'string', enum: ['paciente', 'clinica']),
        new OA\Property(property: 'precio', type: 'number', minimum: 0),
        new OA\Property(property: 'moneda', type: 'string', minLength: 3, maxLength: 3),
        new OA\Property(property: 'periodicidad', type: 'string', enum: ['mensual', 'anual']),
        new OA\Property(property: 'consultasIncluidas', type: 'integer', minimum: 0),
        new OA\Property(property: 'diasPrueba', type: 'integer', minimum: 0),
        new OA\Property(property: 'activo', type: 'boolean'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
    ],
)]
#[OA\Schema(
    schema: 'PlanCrear',
    type: 'object',
    required: ['nombre', 'ambito', 'precio', 'periodicidad'],
    properties: [
        new OA\Property(property: 'nombre', type: 'string', maxLength: 255),
        new OA\Property(property: 'descripcion', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'ambito', type: 'string', enum: ['paciente', 'clinica']),
        new OA\Property(property: 'precio', type: 'number', minimum: 0),
        new OA\Property(property: 'moneda', type: 'string', minLength: 3, maxLength: 3),
        new OA\Property(property: 'periodicidad', type: 'string', enum: ['mensual', 'anual']),
        new OA\Property(property: 'consultasIncluidas', type: 'integer', minimum: 0),
        new OA\Property(property: 'diasPrueba', type: 'integer', minimum: 0),
        new OA\Property(property: 'activo', type: 'boolean'),
    ],
)]
#[OA\Schema(
    schema: 'PlanActualizar',
    type: 'object',
    properties: [
        new OA\Property(property: 'nombre', type: 'string', maxLength: 255),
        new OA\Property(property: 'descripcion', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'ambito', type: 'string', enum: ['paciente', 'clinica']),
        new OA\Property(property: 'precio', type: 'number', minimum: 0),
        new OA\Property(property: 'moneda', type: 'string', minLength: 3, maxLength: 3),
        new OA\Property(property: 'periodicidad', type: 'string', enum: ['mensual', 'anual']),
        new OA\Property(property: 'consultasIncluidas', type: 'integer', minimum: 0),
        new OA\Property(property: 'diasPrueba', type: 'integer', minimum: 0),
        new OA\Property(property: 'activo', type: 'boolean'),
    ],
)]
final class PlanDocs
{
    #[OA\Get(
        path: '/planes',
        tags: ['Plan'],
        summary: 'Listar Plan',
        description: 'Paginado. Filtra por columnas exactas via query string y por rango de creacion (desde/hasta). Solo devuelve lo que esta dentro del alcance del usuario.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/pagina'),
            new OA\Parameter(ref: '#/components/parameters/porPagina'),
            new OA\Parameter(ref: '#/components/parameters/desde'),
            new OA\Parameter(ref: '#/components/parameters/hasta'),
            new OA\Parameter(name: 'orden', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id', 'nombre', 'precio']), description: 'Columna de orden'),
            new OA\Parameter(ref: '#/components/parameters/direccion'),
            new OA\Parameter(name: 'ambito', in: 'query', schema: new OA\Schema(type: 'string', enum: ['paciente', 'clinica']), description: 'Filtro exacto por ambito'),
            new OA\Parameter(name: 'periodicidad', in: 'query', schema: new OA\Schema(type: 'string', enum: ['mensual', 'anual']), description: 'Filtro exacto por periodicidad'),
            new OA\Parameter(name: 'activo', in: 'query', schema: new OA\Schema(type: 'boolean'), description: 'Filtro exacto por activo'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pagina de Plan', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Paginado'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Plan'))])])),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
        ],
    )]
    public function getListar(): void {}

    #[OA\Post(
        path: '/planes',
        tags: ['Plan'],
        summary: 'Crear Plan',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PlanCrear')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/Plan')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function postCrear(): void {}

    #[OA\Get(
        path: '/planes/{id}',
        tags: ['Plan'],
        summary: 'Ver Plan',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Plan', content: new OA\JsonContent(ref: '#/components/schemas/Plan')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    public function getVer(): void {}

    #[OA\Put(
        path: '/planes/{id}',
        tags: ['Plan'],
        summary: 'Actualizar Plan',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PlanActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Plan')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function putActualizar(): void {}

    #[OA\Patch(
        path: '/planes/{id}',
        tags: ['Plan'],
        summary: 'Actualizar Plan',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PlanActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Plan')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function patchActualizar(): void {}

    #[OA\Delete(
        path: '/planes/{id}',
        tags: ['Plan'],
        summary: 'Eliminar Plan',
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
