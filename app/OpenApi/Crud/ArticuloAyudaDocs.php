<?php

// Generado por `php artisan openapi:crud`. No editar a mano.

namespace App\OpenApi\Crud;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Articulo Ayuda', description: 'Lectura: admin, doctor, paciente. Escritura: admin.')]
#[OA\Schema(
    schema: 'ArticuloAyuda',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', readOnly: true),
        new OA\Property(property: 'categoria', type: 'string', maxLength: 100),
        new OA\Property(property: 'titulo', type: 'string', maxLength: 255),
        new OA\Property(property: 'cuerpo', type: 'string'),
        new OA\Property(property: 'orden', type: 'integer', minimum: 0),
        new OA\Property(property: 'publicado', type: 'boolean'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
    ],
)]
#[OA\Schema(
    schema: 'ArticuloAyudaCrear',
    type: 'object',
    required: ['categoria', 'titulo', 'cuerpo'],
    properties: [
        new OA\Property(property: 'categoria', type: 'string', maxLength: 100),
        new OA\Property(property: 'titulo', type: 'string', maxLength: 255),
        new OA\Property(property: 'cuerpo', type: 'string'),
        new OA\Property(property: 'orden', type: 'integer', minimum: 0),
        new OA\Property(property: 'publicado', type: 'boolean'),
    ],
)]
#[OA\Schema(
    schema: 'ArticuloAyudaActualizar',
    type: 'object',
    properties: [
        new OA\Property(property: 'categoria', type: 'string', maxLength: 100),
        new OA\Property(property: 'titulo', type: 'string', maxLength: 255),
        new OA\Property(property: 'cuerpo', type: 'string'),
        new OA\Property(property: 'orden', type: 'integer', minimum: 0),
        new OA\Property(property: 'publicado', type: 'boolean'),
    ],
)]
final class ArticuloAyudaDocs
{
    #[OA\Get(
        path: '/articulos-ayuda',
        tags: ['Articulo Ayuda'],
        summary: 'Listar Articulo Ayuda',
        description: 'Paginado. Filtra por columnas exactas via query string y por rango de creacion (desde/hasta). Solo devuelve lo que esta dentro del alcance del usuario.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/pagina'),
            new OA\Parameter(ref: '#/components/parameters/porPagina'),
            new OA\Parameter(ref: '#/components/parameters/desde'),
            new OA\Parameter(ref: '#/components/parameters/hasta'),
            new OA\Parameter(name: 'orden', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id', 'orden', 'titulo']), description: 'Columna de orden'),
            new OA\Parameter(ref: '#/components/parameters/direccion'),
            new OA\Parameter(name: 'categoria', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 100), description: 'Filtro exacto por categoria'),
            new OA\Parameter(name: 'publicado', in: 'query', schema: new OA\Schema(type: 'boolean'), description: 'Filtro exacto por publicado'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pagina de Articulo Ayuda', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Paginado'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ArticuloAyuda'))])])),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
        ],
    )]
    public function getListar(): void {}

    #[OA\Post(
        path: '/articulos-ayuda',
        tags: ['Articulo Ayuda'],
        summary: 'Crear Articulo Ayuda',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ArticuloAyudaCrear')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/ArticuloAyuda')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function postCrear(): void {}

    #[OA\Get(
        path: '/articulos-ayuda/{id}',
        tags: ['Articulo Ayuda'],
        summary: 'Ver Articulo Ayuda',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Articulo Ayuda', content: new OA\JsonContent(ref: '#/components/schemas/ArticuloAyuda')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    public function getVer(): void {}

    #[OA\Put(
        path: '/articulos-ayuda/{id}',
        tags: ['Articulo Ayuda'],
        summary: 'Actualizar Articulo Ayuda',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ArticuloAyudaActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/ArticuloAyuda')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function putActualizar(): void {}

    #[OA\Patch(
        path: '/articulos-ayuda/{id}',
        tags: ['Articulo Ayuda'],
        summary: 'Actualizar Articulo Ayuda',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ArticuloAyudaActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/ArticuloAyuda')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function patchActualizar(): void {}

    #[OA\Delete(
        path: '/articulos-ayuda/{id}',
        tags: ['Articulo Ayuda'],
        summary: 'Eliminar Articulo Ayuda',
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
