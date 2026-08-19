<?php

// Generado por `php artisan openapi:crud`. No editar a mano.

namespace App\OpenApi\Crud;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Archivo', description: 'Lectura: admin, doctor, paciente. Escritura: admin.')]
#[OA\Schema(
    schema: 'Archivo',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', readOnly: true),
        new OA\Property(property: 'nombre', type: 'string', maxLength: 255),
        new OA\Property(property: 'descripcion', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'ruta', type: 'string', maxLength: 2048),
        new OA\Property(property: 'disk', type: 'string', maxLength: 50),
        new OA\Property(property: 'mime', type: 'string', maxLength: 120, nullable: true),
        new OA\Property(property: 'sizeBytes', type: 'integer', minimum: 0, nullable: true),
        new OA\Property(property: 'hashSha256', type: 'string', minLength: 64, maxLength: 64, nullable: true),
        new OA\Property(property: 'esPrivado', type: 'boolean'),
        new OA\Property(property: 'usuarioId', type: 'integer', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
    ],
)]
#[OA\Schema(
    schema: 'ArchivoCrear',
    type: 'object',
    required: ['nombre', 'ruta'],
    properties: [
        new OA\Property(property: 'nombre', type: 'string', maxLength: 255),
        new OA\Property(property: 'descripcion', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'ruta', type: 'string', maxLength: 2048),
        new OA\Property(property: 'disk', type: 'string', maxLength: 50),
        new OA\Property(property: 'mime', type: 'string', maxLength: 120, nullable: true),
        new OA\Property(property: 'sizeBytes', type: 'integer', minimum: 0, nullable: true),
        new OA\Property(property: 'hashSha256', type: 'string', minLength: 64, maxLength: 64, nullable: true),
        new OA\Property(property: 'esPrivado', type: 'boolean'),
        new OA\Property(property: 'usuarioId', type: 'integer', nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'ArchivoActualizar',
    type: 'object',
    properties: [
        new OA\Property(property: 'nombre', type: 'string', maxLength: 255),
        new OA\Property(property: 'descripcion', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'ruta', type: 'string', maxLength: 2048),
        new OA\Property(property: 'disk', type: 'string', maxLength: 50),
        new OA\Property(property: 'mime', type: 'string', maxLength: 120, nullable: true),
        new OA\Property(property: 'sizeBytes', type: 'integer', minimum: 0, nullable: true),
        new OA\Property(property: 'hashSha256', type: 'string', minLength: 64, maxLength: 64, nullable: true),
        new OA\Property(property: 'esPrivado', type: 'boolean'),
        new OA\Property(property: 'usuarioId', type: 'integer', nullable: true),
    ],
)]
final class ArchivoDocs
{
    #[OA\Get(
        path: '/archivos',
        tags: ['Archivo'],
        summary: 'Listar Archivo',
        description: 'Paginado. Filtra por columnas exactas via query string y por rango de creacion (desde/hasta). Solo devuelve lo que esta dentro del alcance del usuario.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/pagina'),
            new OA\Parameter(ref: '#/components/parameters/porPagina'),
            new OA\Parameter(ref: '#/components/parameters/desde'),
            new OA\Parameter(ref: '#/components/parameters/hasta'),
            new OA\Parameter(name: 'orden', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id', 'created_at', 'sizeBytes']), description: 'Columna de orden'),
            new OA\Parameter(ref: '#/components/parameters/direccion'),
            new OA\Parameter(name: 'usuarioId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por usuarioId'),
            new OA\Parameter(name: 'mime', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 120), description: 'Filtro exacto por mime'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pagina de Archivo', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Paginado'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Archivo'))])])),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
        ],
    )]
    public function getListar(): void {}

    #[OA\Post(
        path: '/archivos',
        tags: ['Archivo'],
        summary: 'Crear Archivo',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ArchivoCrear')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/Archivo')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function postCrear(): void {}

    #[OA\Get(
        path: '/archivos/{id}',
        tags: ['Archivo'],
        summary: 'Ver Archivo',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Archivo', content: new OA\JsonContent(ref: '#/components/schemas/Archivo')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    public function getVer(): void {}

    #[OA\Put(
        path: '/archivos/{id}',
        tags: ['Archivo'],
        summary: 'Actualizar Archivo',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ArchivoActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Archivo')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function putActualizar(): void {}

    #[OA\Patch(
        path: '/archivos/{id}',
        tags: ['Archivo'],
        summary: 'Actualizar Archivo',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ArchivoActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Archivo')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function patchActualizar(): void {}

    #[OA\Delete(
        path: '/archivos/{id}',
        tags: ['Archivo'],
        summary: 'Eliminar Archivo',
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
