<?php

// Generado por `php artisan openapi:crud`. No editar a mano.

namespace App\OpenApi\Crud;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Cita Laboratorio', description: 'Lectura: admin, doctor, paciente. Escritura: admin, doctor, paciente.')]
#[OA\Schema(
    schema: 'CitaLaboratorio',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', readOnly: true),
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'laboratorioId', type: 'integer'),
        new OA\Property(property: 'fecha', type: 'string', format: 'date'),
        new OA\Property(property: 'franja', type: 'string', enum: ['manana', 'tarde']),
        new OA\Property(property: 'direccion', type: 'string', maxLength: 255),
        new OA\Property(property: 'referencia', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'estado', type: 'string', enum: ['agendada', 'realizada', 'cancelada']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
    ],
)]
#[OA\Schema(
    schema: 'CitaLaboratorioCrear',
    type: 'object',
    required: ['pacienteId', 'laboratorioId', 'fecha', 'franja', 'direccion'],
    properties: [
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'laboratorioId', type: 'integer'),
        new OA\Property(property: 'fecha', type: 'string', format: 'date'),
        new OA\Property(property: 'franja', type: 'string', enum: ['manana', 'tarde']),
        new OA\Property(property: 'direccion', type: 'string', maxLength: 255),
        new OA\Property(property: 'referencia', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'estado', type: 'string', enum: ['agendada', 'realizada', 'cancelada']),
    ],
)]
#[OA\Schema(
    schema: 'CitaLaboratorioActualizar',
    type: 'object',
    properties: [
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'laboratorioId', type: 'integer'),
        new OA\Property(property: 'fecha', type: 'string', format: 'date'),
        new OA\Property(property: 'franja', type: 'string', enum: ['manana', 'tarde']),
        new OA\Property(property: 'direccion', type: 'string', maxLength: 255),
        new OA\Property(property: 'referencia', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'estado', type: 'string', enum: ['agendada', 'realizada', 'cancelada']),
    ],
)]
final class CitaLaboratorioDocs
{
    #[OA\Get(
        path: '/citas-laboratorio',
        tags: ['Cita Laboratorio'],
        summary: 'Listar Cita Laboratorio',
        description: 'Paginado. Filtra por columnas exactas via query string y por rango de creacion (desde/hasta). Solo devuelve lo que esta dentro del alcance del usuario.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/pagina'),
            new OA\Parameter(ref: '#/components/parameters/porPagina'),
            new OA\Parameter(ref: '#/components/parameters/desde'),
            new OA\Parameter(ref: '#/components/parameters/hasta'),
            new OA\Parameter(name: 'orden', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id', 'fecha']), description: 'Columna de orden'),
            new OA\Parameter(ref: '#/components/parameters/direccion'),
            new OA\Parameter(name: 'pacienteId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por pacienteId'),
            new OA\Parameter(name: 'laboratorioId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por laboratorioId'),
            new OA\Parameter(name: 'estado', in: 'query', schema: new OA\Schema(type: 'string', enum: ['agendada', 'realizada', 'cancelada']), description: 'Filtro exacto por estado'),
            new OA\Parameter(name: 'franja', in: 'query', schema: new OA\Schema(type: 'string', enum: ['manana', 'tarde']), description: 'Filtro exacto por franja'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pagina de Cita Laboratorio', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Paginado'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/CitaLaboratorio'))])])),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
        ],
    )]
    public function getListar(): void {}

    #[OA\Post(
        path: '/citas-laboratorio',
        tags: ['Cita Laboratorio'],
        summary: 'Crear Cita Laboratorio',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CitaLaboratorioCrear')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/CitaLaboratorio')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function postCrear(): void {}

    #[OA\Get(
        path: '/citas-laboratorio/{id}',
        tags: ['Cita Laboratorio'],
        summary: 'Ver Cita Laboratorio',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Cita Laboratorio', content: new OA\JsonContent(ref: '#/components/schemas/CitaLaboratorio')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    public function getVer(): void {}

    #[OA\Put(
        path: '/citas-laboratorio/{id}',
        tags: ['Cita Laboratorio'],
        summary: 'Actualizar Cita Laboratorio',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CitaLaboratorioActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/CitaLaboratorio')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function putActualizar(): void {}

    #[OA\Patch(
        path: '/citas-laboratorio/{id}',
        tags: ['Cita Laboratorio'],
        summary: 'Actualizar Cita Laboratorio',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CitaLaboratorioActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/CitaLaboratorio')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function patchActualizar(): void {}

    #[OA\Delete(
        path: '/citas-laboratorio/{id}',
        tags: ['Cita Laboratorio'],
        summary: 'Eliminar Cita Laboratorio',
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
