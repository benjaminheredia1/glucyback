<?php

// Generado por `php artisan openapi:crud`. No editar a mano.

namespace App\OpenApi\Crud;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Clinica', description: 'Lectura: admin, doctor, paciente. Escritura: admin.')]
#[OA\Schema(
    schema: 'Clinica',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', readOnly: true),
        new OA\Property(property: 'nombre', type: 'string', maxLength: 255),
        new OA\Property(property: 'descripcion', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'direccion', type: 'string', maxLength: 255),
        new OA\Property(property: 'telefono', type: 'string', maxLength: 50),
        new OA\Property(property: 'estado', type: 'string', enum: ['activa', 'pago_pendiente', 'suspendida']),
        new OA\Property(property: 'nit', type: 'string', maxLength: 50, nullable: true),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, nullable: true),
        new OA\Property(property: 'planId', type: 'integer', nullable: true),
        new OA\Property(property: 'usuarioId', type: 'integer'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
    ],
)]
#[OA\Schema(
    schema: 'ClinicaCrear',
    type: 'object',
    required: ['nombre', 'direccion', 'telefono'],
    properties: [
        new OA\Property(property: 'nombre', type: 'string', maxLength: 255),
        new OA\Property(property: 'descripcion', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'direccion', type: 'string', maxLength: 255),
        new OA\Property(property: 'telefono', type: 'string', maxLength: 50),
        new OA\Property(property: 'estado', type: 'string', enum: ['activa', 'pago_pendiente', 'suspendida']),
        new OA\Property(property: 'nit', type: 'string', maxLength: 50, nullable: true),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, nullable: true),
        new OA\Property(property: 'planId', type: 'integer', nullable: true),
        new OA\Property(property: 'usuarioId', type: 'integer'),
    ],
)]
#[OA\Schema(
    schema: 'ClinicaActualizar',
    type: 'object',
    properties: [
        new OA\Property(property: 'nombre', type: 'string', maxLength: 255),
        new OA\Property(property: 'descripcion', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'direccion', type: 'string', maxLength: 255),
        new OA\Property(property: 'telefono', type: 'string', maxLength: 50),
        new OA\Property(property: 'estado', type: 'string', enum: ['activa', 'pago_pendiente', 'suspendida']),
        new OA\Property(property: 'nit', type: 'string', maxLength: 50, nullable: true),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, nullable: true),
        new OA\Property(property: 'planId', type: 'integer', nullable: true),
        new OA\Property(property: 'usuarioId', type: 'integer'),
    ],
)]
final class ClinicaDocs
{
    #[OA\Get(
        path: '/clinicas',
        tags: ['Clinica'],
        summary: 'Listar Clinica',
        description: 'Paginado. Filtra por columnas exactas via query string y por rango de creacion (desde/hasta). Solo devuelve lo que esta dentro del alcance del usuario.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/pagina'),
            new OA\Parameter(ref: '#/components/parameters/porPagina'),
            new OA\Parameter(ref: '#/components/parameters/desde'),
            new OA\Parameter(ref: '#/components/parameters/hasta'),
            new OA\Parameter(name: 'orden', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id', 'nombre', 'estado']), description: 'Columna de orden'),
            new OA\Parameter(ref: '#/components/parameters/direccion'),
            new OA\Parameter(name: 'estado', in: 'query', schema: new OA\Schema(type: 'string', enum: ['activa', 'pago_pendiente', 'suspendida']), description: 'Filtro exacto por estado'),
            new OA\Parameter(name: 'planId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por planId'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pagina de Clinica', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Paginado'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Clinica'))])])),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
        ],
    )]
    public function getListar(): void {}

    #[OA\Post(
        path: '/clinicas',
        tags: ['Clinica'],
        summary: 'Crear Clinica',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ClinicaCrear')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/Clinica')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function postCrear(): void {}

    #[OA\Get(
        path: '/clinicas/{id}',
        tags: ['Clinica'],
        summary: 'Ver Clinica',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Clinica', content: new OA\JsonContent(ref: '#/components/schemas/Clinica')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    public function getVer(): void {}

    #[OA\Put(
        path: '/clinicas/{id}',
        tags: ['Clinica'],
        summary: 'Actualizar Clinica',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ClinicaActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Clinica')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function putActualizar(): void {}

    #[OA\Patch(
        path: '/clinicas/{id}',
        tags: ['Clinica'],
        summary: 'Actualizar Clinica',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ClinicaActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Clinica')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function patchActualizar(): void {}

    #[OA\Delete(
        path: '/clinicas/{id}',
        tags: ['Clinica'],
        summary: 'Eliminar Clinica',
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
