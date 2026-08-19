<?php

// Generado por `php artisan openapi:crud`. No editar a mano.

namespace App\OpenApi\Crud;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Pago', description: 'Lectura: admin, doctor, paciente. Escritura: admin.')]
#[OA\Schema(
    schema: 'Pago',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', readOnly: true),
        new OA\Property(property: 'suscripcionId', type: 'integer'),
        new OA\Property(property: 'monto', type: 'number', minimum: 0),
        new OA\Property(property: 'moneda', type: 'string', minLength: 3, maxLength: 3),
        new OA\Property(property: 'metodo', type: 'string', enum: ['qr', 'tarjeta', 'transferencia']),
        new OA\Property(property: 'referencia', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
    ],
)]
#[OA\Schema(
    schema: 'PagoCrear',
    type: 'object',
    required: ['suscripcionId', 'monto', 'metodo'],
    properties: [
        new OA\Property(property: 'suscripcionId', type: 'integer'),
        new OA\Property(property: 'monto', type: 'number', minimum: 0),
        new OA\Property(property: 'moneda', type: 'string', minLength: 3, maxLength: 3),
        new OA\Property(property: 'metodo', type: 'string', enum: ['qr', 'tarjeta', 'transferencia']),
        new OA\Property(property: 'referencia', type: 'string', maxLength: 255, nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'PagoActualizar',
    type: 'object',
    properties: [
        new OA\Property(property: 'suscripcionId', type: 'integer'),
        new OA\Property(property: 'monto', type: 'number', minimum: 0),
        new OA\Property(property: 'moneda', type: 'string', minLength: 3, maxLength: 3),
        new OA\Property(property: 'metodo', type: 'string', enum: ['qr', 'tarjeta', 'transferencia']),
        new OA\Property(property: 'referencia', type: 'string', maxLength: 255, nullable: true),
    ],
)]
final class PagoDocs
{
    #[OA\Get(
        path: '/pagos',
        tags: ['Pago'],
        summary: 'Listar Pago',
        description: 'Paginado. Filtra por columnas exactas via query string y por rango de creacion (desde/hasta). Solo devuelve lo que esta dentro del alcance del usuario.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/pagina'),
            new OA\Parameter(ref: '#/components/parameters/porPagina'),
            new OA\Parameter(ref: '#/components/parameters/desde'),
            new OA\Parameter(ref: '#/components/parameters/hasta'),
            new OA\Parameter(name: 'orden', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id', 'pagadoEn', 'monto']), description: 'Columna de orden'),
            new OA\Parameter(ref: '#/components/parameters/direccion'),
            new OA\Parameter(name: 'suscripcionId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por suscripcionId'),
            new OA\Parameter(name: 'estado', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Filtro exacto por estado'),
            new OA\Parameter(name: 'metodo', in: 'query', schema: new OA\Schema(type: 'string', enum: ['qr', 'tarjeta', 'transferencia']), description: 'Filtro exacto por metodo'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pagina de Pago', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Paginado'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Pago'))])])),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
        ],
    )]
    public function getListar(): void {}

    #[OA\Post(
        path: '/pagos',
        tags: ['Pago'],
        summary: 'Crear Pago',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PagoCrear')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/Pago')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function postCrear(): void {}

    #[OA\Get(
        path: '/pagos/{id}',
        tags: ['Pago'],
        summary: 'Ver Pago',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Pago', content: new OA\JsonContent(ref: '#/components/schemas/Pago')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    public function getVer(): void {}

    #[OA\Put(
        path: '/pagos/{id}',
        tags: ['Pago'],
        summary: 'Actualizar Pago',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PagoActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Pago')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function putActualizar(): void {}

    #[OA\Patch(
        path: '/pagos/{id}',
        tags: ['Pago'],
        summary: 'Actualizar Pago',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PagoActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Pago')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function patchActualizar(): void {}

    #[OA\Delete(
        path: '/pagos/{id}',
        tags: ['Pago'],
        summary: 'Eliminar Pago',
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
