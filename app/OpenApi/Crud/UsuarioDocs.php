<?php

// Generado por `php artisan openapi:crud`. No editar a mano.

namespace App\OpenApi\Crud;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Usuario', description: 'Lectura: admin. Escritura: admin.')]
#[OA\Schema(
    schema: 'Usuario',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', readOnly: true),
        new OA\Property(property: 'name', type: 'string', maxLength: 255),
        new OA\Property(property: 'apellidoPaterno', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'apellidoMaterno', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255),
        new OA\Property(property: 'telefono', type: 'string', maxLength: 50, nullable: true),
        new OA\Property(property: 'password', type: 'string'),
        new OA\Property(property: 'rol', type: 'string', enum: ['admin', 'doctor', 'paciente']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
    ],
)]
#[OA\Schema(
    schema: 'UsuarioCrear',
    type: 'object',
    required: ['name', 'email', 'password', 'rol'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255),
        new OA\Property(property: 'apellidoPaterno', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'apellidoMaterno', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255),
        new OA\Property(property: 'telefono', type: 'string', maxLength: 50, nullable: true),
        new OA\Property(property: 'password', type: 'string'),
        new OA\Property(property: 'rol', type: 'string', enum: ['admin', 'doctor', 'paciente']),
    ],
)]
#[OA\Schema(
    schema: 'UsuarioActualizar',
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255),
        new OA\Property(property: 'apellidoPaterno', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'apellidoMaterno', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255),
        new OA\Property(property: 'telefono', type: 'string', maxLength: 50, nullable: true),
        new OA\Property(property: 'password', type: 'string'),
        new OA\Property(property: 'rol', type: 'string', enum: ['admin', 'doctor', 'paciente']),
    ],
)]
final class UsuarioDocs
{
    #[OA\Get(
        path: '/usuarios',
        tags: ['Usuario'],
        summary: 'Listar Usuario',
        description: 'Paginado. Filtra por columnas exactas via query string y por rango de creacion (desde/hasta). Solo devuelve lo que esta dentro del alcance del usuario.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/pagina'),
            new OA\Parameter(ref: '#/components/parameters/porPagina'),
            new OA\Parameter(ref: '#/components/parameters/desde'),
            new OA\Parameter(ref: '#/components/parameters/hasta'),
            new OA\Parameter(name: 'orden', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id', 'name', 'email', 'created_at']), description: 'Columna de orden'),
            new OA\Parameter(ref: '#/components/parameters/direccion'),
            new OA\Parameter(name: 'rol', in: 'query', schema: new OA\Schema(type: 'string', enum: ['admin', 'doctor', 'paciente']), description: 'Filtro exacto por rol'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pagina de Usuario', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Paginado'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Usuario'))])])),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
        ],
    )]
    public function getListar(): void {}

    #[OA\Post(
        path: '/usuarios',
        tags: ['Usuario'],
        summary: 'Crear Usuario',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UsuarioCrear')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/Usuario')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function postCrear(): void {}

    #[OA\Get(
        path: '/usuarios/{id}',
        tags: ['Usuario'],
        summary: 'Ver Usuario',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Usuario', content: new OA\JsonContent(ref: '#/components/schemas/Usuario')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    public function getVer(): void {}

    #[OA\Put(
        path: '/usuarios/{id}',
        tags: ['Usuario'],
        summary: 'Actualizar Usuario',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UsuarioActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Usuario')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function putActualizar(): void {}

    #[OA\Patch(
        path: '/usuarios/{id}',
        tags: ['Usuario'],
        summary: 'Actualizar Usuario',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UsuarioActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Usuario')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function patchActualizar(): void {}

    #[OA\Delete(
        path: '/usuarios/{id}',
        tags: ['Usuario'],
        summary: 'Eliminar Usuario',
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
