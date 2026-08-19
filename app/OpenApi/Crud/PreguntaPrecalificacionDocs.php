<?php

// Generado por `php artisan openapi:crud`. No editar a mano.

namespace App\OpenApi\Crud;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Pregunta Precalificacion', description: 'Lectura: admin, doctor, paciente. Escritura: admin.')]
#[OA\Schema(
    schema: 'PreguntaPrecalificacion',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', readOnly: true),
        new OA\Property(property: 'codigo', type: 'string', maxLength: 50),
        new OA\Property(property: 'texto', type: 'string'),
        new OA\Property(property: 'respuestaAlarma', type: 'string', enum: ['si', 'no']),
        new OA\Property(property: 'esUrgente', type: 'boolean'),
        new OA\Property(property: 'motivo', type: 'string', maxLength: 255),
        new OA\Property(property: 'orden', type: 'integer', minimum: 0),
        new OA\Property(property: 'version', type: 'integer', minimum: 1),
        new OA\Property(property: 'activa', type: 'boolean'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
    ],
)]
#[OA\Schema(
    schema: 'PreguntaPrecalificacionCrear',
    type: 'object',
    required: ['codigo', 'texto', 'respuestaAlarma', 'motivo'],
    properties: [
        new OA\Property(property: 'codigo', type: 'string', maxLength: 50),
        new OA\Property(property: 'texto', type: 'string'),
        new OA\Property(property: 'respuestaAlarma', type: 'string', enum: ['si', 'no']),
        new OA\Property(property: 'esUrgente', type: 'boolean'),
        new OA\Property(property: 'motivo', type: 'string', maxLength: 255),
        new OA\Property(property: 'orden', type: 'integer', minimum: 0),
        new OA\Property(property: 'version', type: 'integer', minimum: 1),
        new OA\Property(property: 'activa', type: 'boolean'),
    ],
)]
#[OA\Schema(
    schema: 'PreguntaPrecalificacionActualizar',
    type: 'object',
    properties: [
        new OA\Property(property: 'codigo', type: 'string', maxLength: 50),
        new OA\Property(property: 'texto', type: 'string'),
        new OA\Property(property: 'respuestaAlarma', type: 'string', enum: ['si', 'no']),
        new OA\Property(property: 'esUrgente', type: 'boolean'),
        new OA\Property(property: 'motivo', type: 'string', maxLength: 255),
        new OA\Property(property: 'orden', type: 'integer', minimum: 0),
        new OA\Property(property: 'version', type: 'integer', minimum: 1),
        new OA\Property(property: 'activa', type: 'boolean'),
    ],
)]
final class PreguntaPrecalificacionDocs
{
    #[OA\Get(
        path: '/preguntas-precalificacion',
        tags: ['Pregunta Precalificacion'],
        summary: 'Listar Pregunta Precalificacion',
        description: 'Paginado. Filtra por columnas exactas via query string y por rango de creacion (desde/hasta). Solo devuelve lo que esta dentro del alcance del usuario.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/pagina'),
            new OA\Parameter(ref: '#/components/parameters/porPagina'),
            new OA\Parameter(ref: '#/components/parameters/desde'),
            new OA\Parameter(ref: '#/components/parameters/hasta'),
            new OA\Parameter(name: 'orden', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id', 'orden', 'version']), description: 'Columna de orden'),
            new OA\Parameter(ref: '#/components/parameters/direccion'),
            new OA\Parameter(name: 'activa', in: 'query', schema: new OA\Schema(type: 'boolean'), description: 'Filtro exacto por activa'),
            new OA\Parameter(name: 'version', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1), description: 'Filtro exacto por version'),
            new OA\Parameter(name: 'esUrgente', in: 'query', schema: new OA\Schema(type: 'boolean'), description: 'Filtro exacto por esUrgente'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pagina de Pregunta Precalificacion', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Paginado'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/PreguntaPrecalificacion'))])])),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
        ],
    )]
    public function getListar(): void {}

    #[OA\Post(
        path: '/preguntas-precalificacion',
        tags: ['Pregunta Precalificacion'],
        summary: 'Crear Pregunta Precalificacion',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PreguntaPrecalificacionCrear')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/PreguntaPrecalificacion')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function postCrear(): void {}

    #[OA\Get(
        path: '/preguntas-precalificacion/{id}',
        tags: ['Pregunta Precalificacion'],
        summary: 'Ver Pregunta Precalificacion',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Pregunta Precalificacion', content: new OA\JsonContent(ref: '#/components/schemas/PreguntaPrecalificacion')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    public function getVer(): void {}

    #[OA\Put(
        path: '/preguntas-precalificacion/{id}',
        tags: ['Pregunta Precalificacion'],
        summary: 'Actualizar Pregunta Precalificacion',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PreguntaPrecalificacionActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/PreguntaPrecalificacion')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function putActualizar(): void {}

    #[OA\Patch(
        path: '/preguntas-precalificacion/{id}',
        tags: ['Pregunta Precalificacion'],
        summary: 'Actualizar Pregunta Precalificacion',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PreguntaPrecalificacionActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/PreguntaPrecalificacion')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function patchActualizar(): void {}

    #[OA\Delete(
        path: '/preguntas-precalificacion/{id}',
        tags: ['Pregunta Precalificacion'],
        summary: 'Eliminar Pregunta Precalificacion',
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
