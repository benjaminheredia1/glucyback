<?php

// Generado por `php artisan openapi:crud`. No editar a mano.

namespace App\OpenApi\Crud;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Doctor Paciente', description: 'Lectura: admin, doctor. Escritura: admin, doctor.')]
#[OA\Schema(
    schema: 'DoctorPaciente',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', readOnly: true),
        new OA\Property(property: 'doctorId', type: 'integer'),
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'desde', type: 'string', format: 'date'),
        new OA\Property(property: 'hasta', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'activo', type: 'boolean'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
    ],
)]
#[OA\Schema(
    schema: 'DoctorPacienteCrear',
    type: 'object',
    required: ['doctorId', 'pacienteId', 'desde'],
    properties: [
        new OA\Property(property: 'doctorId', type: 'integer'),
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'desde', type: 'string', format: 'date'),
        new OA\Property(property: 'hasta', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'activo', type: 'boolean'),
    ],
)]
#[OA\Schema(
    schema: 'DoctorPacienteActualizar',
    type: 'object',
    properties: [
        new OA\Property(property: 'doctorId', type: 'integer'),
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'desde', type: 'string', format: 'date'),
        new OA\Property(property: 'hasta', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'activo', type: 'boolean'),
    ],
)]
final class DoctorPacienteDocs
{
    #[OA\Get(
        path: '/doctor-paciente',
        tags: ['Doctor Paciente'],
        summary: 'Listar Doctor Paciente',
        description: 'Paginado. Filtra por columnas exactas via query string y por rango de creacion (desde/hasta). Solo devuelve lo que esta dentro del alcance del usuario.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/pagina'),
            new OA\Parameter(ref: '#/components/parameters/porPagina'),
            new OA\Parameter(ref: '#/components/parameters/desde'),
            new OA\Parameter(ref: '#/components/parameters/hasta'),
            new OA\Parameter(name: 'orden', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id']), description: 'Columna de orden'),
            new OA\Parameter(ref: '#/components/parameters/direccion'),
            new OA\Parameter(name: 'doctorId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por doctorId'),
            new OA\Parameter(name: 'pacienteId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por pacienteId'),
            new OA\Parameter(name: 'activo', in: 'query', schema: new OA\Schema(type: 'boolean'), description: 'Filtro exacto por activo'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pagina de Doctor Paciente', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Paginado'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/DoctorPaciente'))])])),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
        ],
    )]
    public function getListar(): void {}

    #[OA\Post(
        path: '/doctor-paciente',
        tags: ['Doctor Paciente'],
        summary: 'Crear Doctor Paciente',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/DoctorPacienteCrear')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/DoctorPaciente')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function postCrear(): void {}

    #[OA\Get(
        path: '/doctor-paciente/{id}',
        tags: ['Doctor Paciente'],
        summary: 'Ver Doctor Paciente',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Doctor Paciente', content: new OA\JsonContent(ref: '#/components/schemas/DoctorPaciente')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    public function getVer(): void {}

    #[OA\Put(
        path: '/doctor-paciente/{id}',
        tags: ['Doctor Paciente'],
        summary: 'Actualizar Doctor Paciente',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/DoctorPacienteActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/DoctorPaciente')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function putActualizar(): void {}

    #[OA\Patch(
        path: '/doctor-paciente/{id}',
        tags: ['Doctor Paciente'],
        summary: 'Actualizar Doctor Paciente',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/DoctorPacienteActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/DoctorPaciente')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function patchActualizar(): void {}

    #[OA\Delete(
        path: '/doctor-paciente/{id}',
        tags: ['Doctor Paciente'],
        summary: 'Eliminar Doctor Paciente',
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
