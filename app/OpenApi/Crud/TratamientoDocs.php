<?php

// Generado por `php artisan openapi:crud`. No editar a mano.

namespace App\OpenApi\Crud;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Tratamiento', description: 'Lectura: admin, doctor, paciente. Escritura: admin, doctor.')]
#[OA\Schema(
    schema: 'Tratamiento',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', readOnly: true),
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'doctorId', type: 'integer', nullable: true),
        new OA\Property(property: 'casoId', type: 'integer', nullable: true),
        new OA\Property(property: 'cicloId', type: 'integer', nullable: true),
        new OA\Property(property: 'descripcion', type: 'string', maxLength: 255),
        new OA\Property(property: 'tratamientoAI', type: 'string', nullable: true),
        new OA\Property(property: 'tratamientoDoctor', type: 'string', nullable: true),
        new OA\Property(property: 'notaDoctor', type: 'string', nullable: true),
        new OA\Property(property: 'estado', type: 'string', enum: ['borrador', 'pendiente_firma']),
        new OA\Property(property: 'aceptadoDoctor', type: 'boolean'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
    ],
)]
#[OA\Schema(
    schema: 'TratamientoCrear',
    type: 'object',
    required: ['pacienteId', 'descripcion'],
    properties: [
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'doctorId', type: 'integer', nullable: true),
        new OA\Property(property: 'casoId', type: 'integer', nullable: true),
        new OA\Property(property: 'cicloId', type: 'integer', nullable: true),
        new OA\Property(property: 'descripcion', type: 'string', maxLength: 255),
        new OA\Property(property: 'tratamientoAI', type: 'string', nullable: true),
        new OA\Property(property: 'tratamientoDoctor', type: 'string', nullable: true),
        new OA\Property(property: 'notaDoctor', type: 'string', nullable: true),
        new OA\Property(property: 'estado', type: 'string', enum: ['borrador', 'pendiente_firma']),
        new OA\Property(property: 'aceptadoDoctor', type: 'boolean'),
    ],
)]
#[OA\Schema(
    schema: 'TratamientoActualizar',
    type: 'object',
    properties: [
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'doctorId', type: 'integer', nullable: true),
        new OA\Property(property: 'casoId', type: 'integer', nullable: true),
        new OA\Property(property: 'cicloId', type: 'integer', nullable: true),
        new OA\Property(property: 'descripcion', type: 'string', maxLength: 255),
        new OA\Property(property: 'tratamientoAI', type: 'string', nullable: true),
        new OA\Property(property: 'tratamientoDoctor', type: 'string', nullable: true),
        new OA\Property(property: 'notaDoctor', type: 'string', nullable: true),
        new OA\Property(property: 'estado', type: 'string', enum: ['borrador', 'pendiente_firma']),
        new OA\Property(property: 'aceptadoDoctor', type: 'boolean'),
    ],
)]
final class TratamientoDocs
{
    #[OA\Get(
        path: '/tratamientos',
        tags: ['Tratamiento'],
        summary: 'Listar Tratamiento',
        description: 'Paginado. Filtra por columnas exactas via query string y por rango de creacion (desde/hasta). Solo devuelve lo que esta dentro del alcance del usuario.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/pagina'),
            new OA\Parameter(ref: '#/components/parameters/porPagina'),
            new OA\Parameter(ref: '#/components/parameters/desde'),
            new OA\Parameter(ref: '#/components/parameters/hasta'),
            new OA\Parameter(name: 'orden', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id']), description: 'Columna de orden'),
            new OA\Parameter(ref: '#/components/parameters/direccion'),
            new OA\Parameter(name: 'pacienteId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por pacienteId'),
            new OA\Parameter(name: 'doctorId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por doctorId'),
            new OA\Parameter(name: 'casoId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por casoId'),
            new OA\Parameter(name: 'cicloId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por cicloId'),
            new OA\Parameter(name: 'estado', in: 'query', schema: new OA\Schema(type: 'string', enum: ['borrador', 'pendiente_firma']), description: 'Filtro exacto por estado'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pagina de Tratamiento', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Paginado'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Tratamiento'))])])),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
        ],
    )]
    public function getListar(): void {}

    #[OA\Post(
        path: '/tratamientos',
        tags: ['Tratamiento'],
        summary: 'Crear Tratamiento',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/TratamientoCrear')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/Tratamiento')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function postCrear(): void {}

    #[OA\Get(
        path: '/tratamientos/{id}',
        tags: ['Tratamiento'],
        summary: 'Ver Tratamiento',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Tratamiento', content: new OA\JsonContent(ref: '#/components/schemas/Tratamiento')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    public function getVer(): void {}

    #[OA\Put(
        path: '/tratamientos/{id}',
        tags: ['Tratamiento'],
        summary: 'Actualizar Tratamiento',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/TratamientoActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Tratamiento')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function putActualizar(): void {}

    #[OA\Patch(
        path: '/tratamientos/{id}',
        tags: ['Tratamiento'],
        summary: 'Actualizar Tratamiento',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/TratamientoActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Tratamiento')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function patchActualizar(): void {}

    #[OA\Delete(
        path: '/tratamientos/{id}',
        tags: ['Tratamiento'],
        summary: 'Eliminar Tratamiento',
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
