<?php

// Generado por `php artisan openapi:crud`. No editar a mano.

namespace App\OpenApi\Crud;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Licencia Usuario', description: 'Lectura: admin, doctor. Escritura: admin.')]
#[OA\Schema(
    schema: 'LicenciaUsuario',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', readOnly: true),
        new OA\Property(property: 'licenciaId', type: 'integer'),
        new OA\Property(property: 'usuarioId', type: 'integer'),
        new OA\Property(property: 'estado', type: 'string', enum: ['activa', 'revocada']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
    ],
)]
#[OA\Schema(
    schema: 'LicenciaUsuarioCrear',
    type: 'object',
    required: ['licenciaId', 'usuarioId'],
    properties: [
        new OA\Property(property: 'licenciaId', type: 'integer'),
        new OA\Property(property: 'usuarioId', type: 'integer'),
        new OA\Property(property: 'estado', type: 'string', enum: ['activa', 'revocada']),
    ],
)]
#[OA\Schema(
    schema: 'LicenciaUsuarioActualizar',
    type: 'object',
    properties: [
        new OA\Property(property: 'licenciaId', type: 'integer'),
        new OA\Property(property: 'usuarioId', type: 'integer'),
        new OA\Property(property: 'estado', type: 'string', enum: ['activa', 'revocada']),
    ],
)]
final class LicenciaUsuarioDocs
{
    #[OA\Get(
        path: '/licencia-usuarios',
        tags: ['Licencia Usuario'],
        summary: 'Listar Licencia Usuario',
        description: 'Paginado. Filtra por columnas exactas via query string y por rango de creacion (desde/hasta). Solo devuelve lo que esta dentro del alcance del usuario.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/pagina'),
            new OA\Parameter(ref: '#/components/parameters/porPagina'),
            new OA\Parameter(ref: '#/components/parameters/desde'),
            new OA\Parameter(ref: '#/components/parameters/hasta'),
            new OA\Parameter(name: 'orden', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id']), description: 'Columna de orden'),
            new OA\Parameter(ref: '#/components/parameters/direccion'),
            new OA\Parameter(name: 'licenciaId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por licenciaId'),
            new OA\Parameter(name: 'usuarioId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por usuarioId'),
            new OA\Parameter(name: 'estado', in: 'query', schema: new OA\Schema(type: 'string', enum: ['activa', 'revocada']), description: 'Filtro exacto por estado'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pagina de Licencia Usuario', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Paginado'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/LicenciaUsuario'))])])),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
        ],
    )]
    public function getListar(): void {}

    #[OA\Post(
        path: '/licencia-usuarios',
        tags: ['Licencia Usuario'],
        summary: 'Crear Licencia Usuario',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/LicenciaUsuarioCrear')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/LicenciaUsuario')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function postCrear(): void {}

    #[OA\Get(
        path: '/licencia-usuarios/{id}',
        tags: ['Licencia Usuario'],
        summary: 'Ver Licencia Usuario',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Licencia Usuario', content: new OA\JsonContent(ref: '#/components/schemas/LicenciaUsuario')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    public function getVer(): void {}

    #[OA\Put(
        path: '/licencia-usuarios/{id}',
        tags: ['Licencia Usuario'],
        summary: 'Actualizar Licencia Usuario',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/LicenciaUsuarioActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/LicenciaUsuario')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function putActualizar(): void {}

    #[OA\Patch(
        path: '/licencia-usuarios/{id}',
        tags: ['Licencia Usuario'],
        summary: 'Actualizar Licencia Usuario',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/LicenciaUsuarioActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/LicenciaUsuario')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function patchActualizar(): void {}

    #[OA\Delete(
        path: '/licencia-usuarios/{id}',
        tags: ['Licencia Usuario'],
        summary: 'Eliminar Licencia Usuario',
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
