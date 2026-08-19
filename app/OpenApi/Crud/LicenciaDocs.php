<?php

// Generado por `php artisan openapi:crud`. No editar a mano.

namespace App\OpenApi\Crud;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Licencia', description: 'Lectura: admin, doctor. Escritura: admin.')]
#[OA\Schema(
    schema: 'Licencia',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', readOnly: true),
        new OA\Property(property: 'codigo', type: 'string', maxLength: 255),
        new OA\Property(property: 'nombre', type: 'string', maxLength: 255),
        new OA\Property(property: 'clinicaId', type: 'integer'),
        new OA\Property(property: 'planId', type: 'integer', nullable: true),
        new OA\Property(property: 'cantidad', type: 'integer', minimum: 1),
        new OA\Property(property: 'fecha_expiracion', type: 'string', format: 'date'),
        new OA\Property(property: 'descuento', type: 'number', minimum: 0, maximum: 100),
        new OA\Property(property: 'estado', type: 'string', enum: ['activa', 'inactiva', 'suspendida', 'vencida']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
    ],
)]
#[OA\Schema(
    schema: 'LicenciaCrear',
    type: 'object',
    required: ['codigo', 'nombre', 'clinicaId', 'cantidad', 'fecha_expiracion', 'descuento'],
    properties: [
        new OA\Property(property: 'codigo', type: 'string', maxLength: 255),
        new OA\Property(property: 'nombre', type: 'string', maxLength: 255),
        new OA\Property(property: 'clinicaId', type: 'integer'),
        new OA\Property(property: 'planId', type: 'integer', nullable: true),
        new OA\Property(property: 'cantidad', type: 'integer', minimum: 1),
        new OA\Property(property: 'fecha_expiracion', type: 'string', format: 'date'),
        new OA\Property(property: 'descuento', type: 'number', minimum: 0, maximum: 100),
        new OA\Property(property: 'estado', type: 'string', enum: ['activa', 'inactiva', 'suspendida', 'vencida']),
    ],
)]
#[OA\Schema(
    schema: 'LicenciaActualizar',
    type: 'object',
    properties: [
        new OA\Property(property: 'codigo', type: 'string', maxLength: 255),
        new OA\Property(property: 'nombre', type: 'string', maxLength: 255),
        new OA\Property(property: 'clinicaId', type: 'integer'),
        new OA\Property(property: 'planId', type: 'integer', nullable: true),
        new OA\Property(property: 'cantidad', type: 'integer', minimum: 1),
        new OA\Property(property: 'fecha_expiracion', type: 'string', format: 'date'),
        new OA\Property(property: 'descuento', type: 'number', minimum: 0, maximum: 100),
        new OA\Property(property: 'estado', type: 'string', enum: ['activa', 'inactiva', 'suspendida', 'vencida']),
    ],
)]
final class LicenciaDocs
{
    #[OA\Get(
        path: '/licencias',
        tags: ['Licencia'],
        summary: 'Listar Licencia',
        description: 'Paginado. Filtra por columnas exactas via query string y por rango de creacion (desde/hasta). Solo devuelve lo que esta dentro del alcance del usuario.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/pagina'),
            new OA\Parameter(ref: '#/components/parameters/porPagina'),
            new OA\Parameter(ref: '#/components/parameters/desde'),
            new OA\Parameter(ref: '#/components/parameters/hasta'),
            new OA\Parameter(name: 'orden', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id', 'fecha_expiracion', 'codigo']), description: 'Columna de orden'),
            new OA\Parameter(ref: '#/components/parameters/direccion'),
            new OA\Parameter(name: 'clinicaId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por clinicaId'),
            new OA\Parameter(name: 'planId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por planId'),
            new OA\Parameter(name: 'estado', in: 'query', schema: new OA\Schema(type: 'string', enum: ['activa', 'inactiva', 'suspendida', 'vencida']), description: 'Filtro exacto por estado'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pagina de Licencia', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Paginado'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Licencia'))])])),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
        ],
    )]
    public function getListar(): void {}

    #[OA\Post(
        path: '/licencias',
        tags: ['Licencia'],
        summary: 'Crear Licencia',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/LicenciaCrear')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/Licencia')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function postCrear(): void {}

    #[OA\Get(
        path: '/licencias/{id}',
        tags: ['Licencia'],
        summary: 'Ver Licencia',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Licencia', content: new OA\JsonContent(ref: '#/components/schemas/Licencia')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    public function getVer(): void {}

    #[OA\Put(
        path: '/licencias/{id}',
        tags: ['Licencia'],
        summary: 'Actualizar Licencia',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/LicenciaActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Licencia')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function putActualizar(): void {}

    #[OA\Patch(
        path: '/licencias/{id}',
        tags: ['Licencia'],
        summary: 'Actualizar Licencia',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/LicenciaActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Licencia')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function patchActualizar(): void {}

    #[OA\Delete(
        path: '/licencias/{id}',
        tags: ['Licencia'],
        summary: 'Eliminar Licencia',
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
