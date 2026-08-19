<?php

// Generado por `php artisan openapi:crud`. No editar a mano.

namespace App\OpenApi\Crud;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Alerta', description: 'Lectura: admin, doctor, paciente. Escritura: admin, doctor.')]
#[OA\Schema(
    schema: 'Alerta',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', readOnly: true),
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'medicionId', type: 'integer', nullable: true),
        new OA\Property(property: 'reglaId', type: 'integer', nullable: true),
        new OA\Property(property: 'casoId', type: 'integer', nullable: true),
        new OA\Property(property: 'tipo', type: 'string', enum: ['valor_critico', 'sin_registro', 'estudio_vencido', 'ciclo_vencido']),
        new OA\Property(property: 'severidad', type: 'string', enum: ['critica', 'alta', 'media']),
        new OA\Property(property: 'mensaje', type: 'string', maxLength: 255),
        new OA\Property(property: 'estado', type: 'string', enum: ['abierta', 'vista', 'atendida']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
    ],
)]
#[OA\Schema(
    schema: 'AlertaCrear',
    type: 'object',
    required: ['pacienteId', 'tipo', 'severidad', 'mensaje'],
    properties: [
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'medicionId', type: 'integer', nullable: true),
        new OA\Property(property: 'reglaId', type: 'integer', nullable: true),
        new OA\Property(property: 'casoId', type: 'integer', nullable: true),
        new OA\Property(property: 'tipo', type: 'string', enum: ['valor_critico', 'sin_registro', 'estudio_vencido', 'ciclo_vencido']),
        new OA\Property(property: 'severidad', type: 'string', enum: ['critica', 'alta', 'media']),
        new OA\Property(property: 'mensaje', type: 'string', maxLength: 255),
        new OA\Property(property: 'estado', type: 'string', enum: ['abierta', 'vista', 'atendida']),
    ],
)]
#[OA\Schema(
    schema: 'AlertaActualizar',
    type: 'object',
    properties: [
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'medicionId', type: 'integer', nullable: true),
        new OA\Property(property: 'reglaId', type: 'integer', nullable: true),
        new OA\Property(property: 'casoId', type: 'integer', nullable: true),
        new OA\Property(property: 'tipo', type: 'string', enum: ['valor_critico', 'sin_registro', 'estudio_vencido', 'ciclo_vencido']),
        new OA\Property(property: 'severidad', type: 'string', enum: ['critica', 'alta', 'media']),
        new OA\Property(property: 'mensaje', type: 'string', maxLength: 255),
        new OA\Property(property: 'estado', type: 'string', enum: ['abierta', 'vista', 'atendida']),
    ],
)]
final class AlertaDocs
{
    #[OA\Get(
        path: '/alertas',
        tags: ['Alerta'],
        summary: 'Listar Alerta',
        description: 'Paginado. Filtra por columnas exactas via query string y por rango de creacion (desde/hasta). Solo devuelve lo que esta dentro del alcance del usuario.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/pagina'),
            new OA\Parameter(ref: '#/components/parameters/porPagina'),
            new OA\Parameter(ref: '#/components/parameters/desde'),
            new OA\Parameter(ref: '#/components/parameters/hasta'),
            new OA\Parameter(name: 'orden', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id', 'created_at', 'severidad']), description: 'Columna de orden'),
            new OA\Parameter(ref: '#/components/parameters/direccion'),
            new OA\Parameter(name: 'pacienteId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por pacienteId'),
            new OA\Parameter(name: 'tipo', in: 'query', schema: new OA\Schema(type: 'string', enum: ['valor_critico', 'sin_registro', 'estudio_vencido', 'ciclo_vencido']), description: 'Filtro exacto por tipo'),
            new OA\Parameter(name: 'severidad', in: 'query', schema: new OA\Schema(type: 'string', enum: ['critica', 'alta', 'media']), description: 'Filtro exacto por severidad'),
            new OA\Parameter(name: 'estado', in: 'query', schema: new OA\Schema(type: 'string', enum: ['abierta', 'vista', 'atendida']), description: 'Filtro exacto por estado'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pagina de Alerta', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Paginado'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Alerta'))])])),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
        ],
    )]
    public function getListar(): void {}

    #[OA\Post(
        path: '/alertas',
        tags: ['Alerta'],
        summary: 'Crear Alerta',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AlertaCrear')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/Alerta')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function postCrear(): void {}

    #[OA\Get(
        path: '/alertas/{id}',
        tags: ['Alerta'],
        summary: 'Ver Alerta',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Alerta', content: new OA\JsonContent(ref: '#/components/schemas/Alerta')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    public function getVer(): void {}

    #[OA\Put(
        path: '/alertas/{id}',
        tags: ['Alerta'],
        summary: 'Actualizar Alerta',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AlertaActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Alerta')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function putActualizar(): void {}

    #[OA\Patch(
        path: '/alertas/{id}',
        tags: ['Alerta'],
        summary: 'Actualizar Alerta',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AlertaActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Alerta')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function patchActualizar(): void {}

    #[OA\Delete(
        path: '/alertas/{id}',
        tags: ['Alerta'],
        summary: 'Eliminar Alerta',
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
