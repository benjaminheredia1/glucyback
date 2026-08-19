<?php

// Generado por `php artisan openapi:crud`. No editar a mano.

namespace App\OpenApi\Crud;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Medicion', description: 'Lectura: admin, doctor, paciente. Escritura: admin, doctor, paciente.')]
#[OA\Schema(
    schema: 'Medicion',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', readOnly: true),
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'cicloId', type: 'integer', nullable: true),
        new OA\Property(property: 'valor', type: 'number', minimum: 10, maximum: 900),
        new OA\Property(property: 'unidad', type: 'string', enum: ['mg/dL', 'mmol/L']),
        new OA\Property(property: 'momento', type: 'string', enum: ['ayunas', 'preprandial', 'postprandial', 'nocturna']),
        new OA\Property(property: 'fuente', type: 'string', enum: ['manual', 'dispositivo']),
        new OA\Property(property: 'medidoEn', type: 'string', format: 'date'),
        new OA\Property(property: 'nota', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
    ],
)]
#[OA\Schema(
    schema: 'MedicionCrear',
    type: 'object',
    required: ['pacienteId', 'valor', 'momento'],
    properties: [
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'cicloId', type: 'integer', nullable: true),
        new OA\Property(property: 'valor', type: 'number', minimum: 10, maximum: 900),
        new OA\Property(property: 'unidad', type: 'string', enum: ['mg/dL', 'mmol/L']),
        new OA\Property(property: 'momento', type: 'string', enum: ['ayunas', 'preprandial', 'postprandial', 'nocturna']),
        new OA\Property(property: 'fuente', type: 'string', enum: ['manual', 'dispositivo']),
        new OA\Property(property: 'medidoEn', type: 'string', format: 'date'),
        new OA\Property(property: 'nota', type: 'string', maxLength: 255, nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'MedicionActualizar',
    type: 'object',
    properties: [
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'cicloId', type: 'integer', nullable: true),
        new OA\Property(property: 'valor', type: 'number', minimum: 10, maximum: 900),
        new OA\Property(property: 'unidad', type: 'string', enum: ['mg/dL', 'mmol/L']),
        new OA\Property(property: 'momento', type: 'string', enum: ['ayunas', 'preprandial', 'postprandial', 'nocturna']),
        new OA\Property(property: 'fuente', type: 'string', enum: ['manual', 'dispositivo']),
        new OA\Property(property: 'medidoEn', type: 'string', format: 'date'),
        new OA\Property(property: 'nota', type: 'string', maxLength: 255, nullable: true),
    ],
)]
final class MedicionDocs
{
    #[OA\Get(
        path: '/mediciones',
        tags: ['Medicion'],
        summary: 'Listar Medicion',
        description: 'Paginado. Filtra por columnas exactas via query string y por rango de creacion (desde/hasta). Solo devuelve lo que esta dentro del alcance del usuario.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/pagina'),
            new OA\Parameter(ref: '#/components/parameters/porPagina'),
            new OA\Parameter(ref: '#/components/parameters/desde'),
            new OA\Parameter(ref: '#/components/parameters/hasta'),
            new OA\Parameter(name: 'orden', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id', 'medidoEn', 'valor']), description: 'Columna de orden'),
            new OA\Parameter(ref: '#/components/parameters/direccion'),
            new OA\Parameter(name: 'pacienteId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por pacienteId'),
            new OA\Parameter(name: 'cicloId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por cicloId'),
            new OA\Parameter(name: 'momento', in: 'query', schema: new OA\Schema(type: 'string', enum: ['ayunas', 'preprandial', 'postprandial', 'nocturna']), description: 'Filtro exacto por momento'),
            new OA\Parameter(name: 'fuente', in: 'query', schema: new OA\Schema(type: 'string', enum: ['manual', 'dispositivo']), description: 'Filtro exacto por fuente'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pagina de Medicion', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Paginado'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Medicion'))])])),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
        ],
    )]
    public function getListar(): void {}

    #[OA\Post(
        path: '/mediciones',
        tags: ['Medicion'],
        summary: 'Crear Medicion',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/MedicionCrear')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/Medicion')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function postCrear(): void {}

    #[OA\Get(
        path: '/mediciones/{id}',
        tags: ['Medicion'],
        summary: 'Ver Medicion',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Medicion', content: new OA\JsonContent(ref: '#/components/schemas/Medicion')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    public function getVer(): void {}

    #[OA\Put(
        path: '/mediciones/{id}',
        tags: ['Medicion'],
        summary: 'Actualizar Medicion',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/MedicionActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Medicion')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function putActualizar(): void {}

    #[OA\Patch(
        path: '/mediciones/{id}',
        tags: ['Medicion'],
        summary: 'Actualizar Medicion',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/MedicionActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Medicion')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function patchActualizar(): void {}

    #[OA\Delete(
        path: '/mediciones/{id}',
        tags: ['Medicion'],
        summary: 'Eliminar Medicion',
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
