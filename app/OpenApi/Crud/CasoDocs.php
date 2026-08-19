<?php

// Generado por `php artisan openapi:crud`. No editar a mano.

namespace App\OpenApi\Crud;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Caso', description: 'Lectura: admin, doctor. Escritura: admin, doctor.')]
#[OA\Schema(
    schema: 'Caso',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', readOnly: true),
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'doctorId', type: 'integer', nullable: true),
        new OA\Property(property: 'cicloId', type: 'integer', nullable: true),
        new OA\Property(property: 'tipo', type: 'string', enum: ['ingreso', 'ajuste_ciclo', 'revision_15d', 'alerta']),
        new OA\Property(property: 'urgencia', type: 'string', enum: ['urgente', 'pendiente', 'estable']),
        new OA\Property(property: 'titulo', type: 'string', maxLength: 255),
        new OA\Property(property: 'nota', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
    ],
)]
#[OA\Schema(
    schema: 'CasoCrear',
    type: 'object',
    required: ['pacienteId', 'tipo', 'titulo'],
    properties: [
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'doctorId', type: 'integer', nullable: true),
        new OA\Property(property: 'cicloId', type: 'integer', nullable: true),
        new OA\Property(property: 'tipo', type: 'string', enum: ['ingreso', 'ajuste_ciclo', 'revision_15d', 'alerta']),
        new OA\Property(property: 'urgencia', type: 'string', enum: ['urgente', 'pendiente', 'estable']),
        new OA\Property(property: 'titulo', type: 'string', maxLength: 255),
        new OA\Property(property: 'nota', type: 'string', nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'CasoActualizar',
    type: 'object',
    properties: [
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'doctorId', type: 'integer', nullable: true),
        new OA\Property(property: 'cicloId', type: 'integer', nullable: true),
        new OA\Property(property: 'tipo', type: 'string', enum: ['ingreso', 'ajuste_ciclo', 'revision_15d', 'alerta']),
        new OA\Property(property: 'urgencia', type: 'string', enum: ['urgente', 'pendiente', 'estable']),
        new OA\Property(property: 'titulo', type: 'string', maxLength: 255),
        new OA\Property(property: 'nota', type: 'string', nullable: true),
    ],
)]
final class CasoDocs
{
    #[OA\Get(
        path: '/casos',
        tags: ['Caso'],
        summary: 'Listar Caso',
        description: 'Paginado. Filtra por columnas exactas via query string y por rango de creacion (desde/hasta). Solo devuelve lo que esta dentro del alcance del usuario.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/pagina'),
            new OA\Parameter(ref: '#/components/parameters/porPagina'),
            new OA\Parameter(ref: '#/components/parameters/desde'),
            new OA\Parameter(ref: '#/components/parameters/hasta'),
            new OA\Parameter(name: 'orden', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id', 'abiertoEn', 'urgencia', 'cerradoEn']), description: 'Columna de orden'),
            new OA\Parameter(ref: '#/components/parameters/direccion'),
            new OA\Parameter(name: 'pacienteId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por pacienteId'),
            new OA\Parameter(name: 'doctorId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por doctorId'),
            new OA\Parameter(name: 'tipo', in: 'query', schema: new OA\Schema(type: 'string', enum: ['ingreso', 'ajuste_ciclo', 'revision_15d', 'alerta']), description: 'Filtro exacto por tipo'),
            new OA\Parameter(name: 'urgencia', in: 'query', schema: new OA\Schema(type: 'string', enum: ['urgente', 'pendiente', 'estable']), description: 'Filtro exacto por urgencia'),
            new OA\Parameter(name: 'estado', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Filtro exacto por estado'),
            new OA\Parameter(name: 'cicloId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por cicloId'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pagina de Caso', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Paginado'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Caso'))])])),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
        ],
    )]
    public function getListar(): void {}

    #[OA\Post(
        path: '/casos',
        tags: ['Caso'],
        summary: 'Crear Caso',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CasoCrear')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/Caso')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function postCrear(): void {}

    #[OA\Get(
        path: '/casos/{id}',
        tags: ['Caso'],
        summary: 'Ver Caso',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Caso', content: new OA\JsonContent(ref: '#/components/schemas/Caso')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    public function getVer(): void {}

    #[OA\Put(
        path: '/casos/{id}',
        tags: ['Caso'],
        summary: 'Actualizar Caso',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CasoActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Caso')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function putActualizar(): void {}

    #[OA\Patch(
        path: '/casos/{id}',
        tags: ['Caso'],
        summary: 'Actualizar Caso',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CasoActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Caso')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function patchActualizar(): void {}

    #[OA\Delete(
        path: '/casos/{id}',
        tags: ['Caso'],
        summary: 'Eliminar Caso',
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
