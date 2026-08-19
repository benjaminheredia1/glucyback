<?php

// Generado por `php artisan openapi:crud`. No editar a mano.

namespace App\OpenApi\Crud;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Precalificacion Respuesta', description: 'Lectura: admin, doctor. Escritura: admin.')]
#[OA\Schema(
    schema: 'PrecalificacionRespuesta',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', readOnly: true),
        new OA\Property(property: 'precalificacionId', type: 'integer'),
        new OA\Property(property: 'preguntaId', type: 'integer'),
        new OA\Property(property: 'respuesta', type: 'string', enum: ['si', 'no']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
    ],
)]
#[OA\Schema(
    schema: 'PrecalificacionRespuestaCrear',
    type: 'object',
    required: ['precalificacionId', 'preguntaId', 'respuesta'],
    properties: [
        new OA\Property(property: 'precalificacionId', type: 'integer'),
        new OA\Property(property: 'preguntaId', type: 'integer'),
        new OA\Property(property: 'respuesta', type: 'string', enum: ['si', 'no']),
    ],
)]
#[OA\Schema(
    schema: 'PrecalificacionRespuestaActualizar',
    type: 'object',
    properties: [
        new OA\Property(property: 'precalificacionId', type: 'integer'),
        new OA\Property(property: 'preguntaId', type: 'integer'),
        new OA\Property(property: 'respuesta', type: 'string', enum: ['si', 'no']),
    ],
)]
final class PrecalificacionRespuestaDocs
{
    #[OA\Get(
        path: '/precalificacion-respuestas',
        tags: ['Precalificacion Respuesta'],
        summary: 'Listar Precalificacion Respuesta',
        description: 'Paginado. Filtra por columnas exactas via query string y por rango de creacion (desde/hasta). Solo devuelve lo que esta dentro del alcance del usuario.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/pagina'),
            new OA\Parameter(ref: '#/components/parameters/porPagina'),
            new OA\Parameter(ref: '#/components/parameters/desde'),
            new OA\Parameter(ref: '#/components/parameters/hasta'),
            new OA\Parameter(name: 'orden', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id']), description: 'Columna de orden'),
            new OA\Parameter(ref: '#/components/parameters/direccion'),
            new OA\Parameter(name: 'precalificacionId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por precalificacionId'),
            new OA\Parameter(name: 'preguntaId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por preguntaId'),
            new OA\Parameter(name: 'respuesta', in: 'query', schema: new OA\Schema(type: 'string', enum: ['si', 'no']), description: 'Filtro exacto por respuesta'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pagina de Precalificacion Respuesta', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Paginado'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/PrecalificacionRespuesta'))])])),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
        ],
    )]
    public function getListar(): void {}

    #[OA\Post(
        path: '/precalificacion-respuestas',
        tags: ['Precalificacion Respuesta'],
        summary: 'Crear Precalificacion Respuesta',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PrecalificacionRespuestaCrear')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/PrecalificacionRespuesta')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function postCrear(): void {}

    #[OA\Get(
        path: '/precalificacion-respuestas/{id}',
        tags: ['Precalificacion Respuesta'],
        summary: 'Ver Precalificacion Respuesta',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Precalificacion Respuesta', content: new OA\JsonContent(ref: '#/components/schemas/PrecalificacionRespuesta')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    public function getVer(): void {}

    #[OA\Put(
        path: '/precalificacion-respuestas/{id}',
        tags: ['Precalificacion Respuesta'],
        summary: 'Actualizar Precalificacion Respuesta',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PrecalificacionRespuestaActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/PrecalificacionRespuesta')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function putActualizar(): void {}

    #[OA\Patch(
        path: '/precalificacion-respuestas/{id}',
        tags: ['Precalificacion Respuesta'],
        summary: 'Actualizar Precalificacion Respuesta',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PrecalificacionRespuestaActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/PrecalificacionRespuesta')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function patchActualizar(): void {}

    #[OA\Delete(
        path: '/precalificacion-respuestas/{id}',
        tags: ['Precalificacion Respuesta'],
        summary: 'Eliminar Precalificacion Respuesta',
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
