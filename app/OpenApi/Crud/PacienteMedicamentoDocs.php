<?php

// Generado por `php artisan openapi:crud`. No editar a mano.

namespace App\OpenApi\Crud;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Paciente Medicamento', description: 'Lectura: admin, doctor, paciente. Escritura: admin, doctor.')]
#[OA\Schema(
    schema: 'PacienteMedicamento',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', readOnly: true),
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'tratamientoId', type: 'integer', nullable: true),
        new OA\Property(property: 'medicamentoId', type: 'integer'),
        new OA\Property(property: 'dosis', type: 'string', maxLength: 255),
        new OA\Property(property: 'frecuencia', type: 'string', maxLength: 255),
        new OA\Property(property: 'horarios', type: 'array', minimum: 1, maximum: 6, items: new OA\Items(type: 'string', format: 'date-time')),
        new OA\Property(property: 'indicaciones', type: 'string', nullable: true),
        new OA\Property(property: 'fechaInicio', type: 'string', format: 'date'),
        new OA\Property(property: 'fechaFin', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'activo', type: 'boolean'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
    ],
)]
#[OA\Schema(
    schema: 'PacienteMedicamentoCrear',
    type: 'object',
    required: ['pacienteId', 'medicamentoId', 'dosis', 'frecuencia', 'horarios', 'fechaInicio'],
    properties: [
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'tratamientoId', type: 'integer', nullable: true),
        new OA\Property(property: 'medicamentoId', type: 'integer'),
        new OA\Property(property: 'dosis', type: 'string', maxLength: 255),
        new OA\Property(property: 'frecuencia', type: 'string', maxLength: 255),
        new OA\Property(property: 'horarios', type: 'array', minimum: 1, maximum: 6, items: new OA\Items(type: 'string', format: 'date-time')),
        new OA\Property(property: 'indicaciones', type: 'string', nullable: true),
        new OA\Property(property: 'fechaInicio', type: 'string', format: 'date'),
        new OA\Property(property: 'fechaFin', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'activo', type: 'boolean'),
    ],
)]
#[OA\Schema(
    schema: 'PacienteMedicamentoActualizar',
    type: 'object',
    properties: [
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'tratamientoId', type: 'integer', nullable: true),
        new OA\Property(property: 'medicamentoId', type: 'integer'),
        new OA\Property(property: 'dosis', type: 'string', maxLength: 255),
        new OA\Property(property: 'frecuencia', type: 'string', maxLength: 255),
        new OA\Property(property: 'horarios', type: 'array', minimum: 1, maximum: 6, items: new OA\Items(type: 'string', format: 'date-time')),
        new OA\Property(property: 'indicaciones', type: 'string', nullable: true),
        new OA\Property(property: 'fechaInicio', type: 'string', format: 'date'),
        new OA\Property(property: 'fechaFin', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'activo', type: 'boolean'),
    ],
)]
final class PacienteMedicamentoDocs
{
    #[OA\Get(
        path: '/paciente-medicamentos',
        tags: ['Paciente Medicamento'],
        summary: 'Listar Paciente Medicamento',
        description: 'Paginado. Filtra por columnas exactas via query string y por rango de creacion (desde/hasta). Solo devuelve lo que esta dentro del alcance del usuario.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/pagina'),
            new OA\Parameter(ref: '#/components/parameters/porPagina'),
            new OA\Parameter(ref: '#/components/parameters/desde'),
            new OA\Parameter(ref: '#/components/parameters/hasta'),
            new OA\Parameter(name: 'orden', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id', 'fechaInicio']), description: 'Columna de orden'),
            new OA\Parameter(ref: '#/components/parameters/direccion'),
            new OA\Parameter(name: 'pacienteId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por pacienteId'),
            new OA\Parameter(name: 'medicamentoId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por medicamentoId'),
            new OA\Parameter(name: 'tratamientoId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por tratamientoId'),
            new OA\Parameter(name: 'activo', in: 'query', schema: new OA\Schema(type: 'boolean'), description: 'Filtro exacto por activo'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pagina de Paciente Medicamento', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Paginado'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/PacienteMedicamento'))])])),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
        ],
    )]
    public function getListar(): void {}

    #[OA\Post(
        path: '/paciente-medicamentos',
        tags: ['Paciente Medicamento'],
        summary: 'Crear Paciente Medicamento',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PacienteMedicamentoCrear')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/PacienteMedicamento')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function postCrear(): void {}

    #[OA\Get(
        path: '/paciente-medicamentos/{id}',
        tags: ['Paciente Medicamento'],
        summary: 'Ver Paciente Medicamento',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Paciente Medicamento', content: new OA\JsonContent(ref: '#/components/schemas/PacienteMedicamento')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    public function getVer(): void {}

    #[OA\Put(
        path: '/paciente-medicamentos/{id}',
        tags: ['Paciente Medicamento'],
        summary: 'Actualizar Paciente Medicamento',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PacienteMedicamentoActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/PacienteMedicamento')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function putActualizar(): void {}

    #[OA\Patch(
        path: '/paciente-medicamentos/{id}',
        tags: ['Paciente Medicamento'],
        summary: 'Actualizar Paciente Medicamento',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PacienteMedicamentoActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/PacienteMedicamento')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function patchActualizar(): void {}

    #[OA\Delete(
        path: '/paciente-medicamentos/{id}',
        tags: ['Paciente Medicamento'],
        summary: 'Eliminar Paciente Medicamento',
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
