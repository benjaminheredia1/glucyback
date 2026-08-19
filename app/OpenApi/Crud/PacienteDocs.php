<?php

// Generado por `php artisan openapi:crud`. No editar a mano.

namespace App\OpenApi\Crud;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Paciente', description: 'Lectura: admin, doctor, paciente. Escritura: admin, doctor.')]
#[OA\Schema(
    schema: 'Paciente',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', readOnly: true),
        new OA\Property(property: 'usuarioId', type: 'integer'),
        new OA\Property(property: 'usuario', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'clinicaId', type: 'integer', nullable: true),
        new OA\Property(property: 'fechaNacimiento', type: 'string', format: 'date'),
        new OA\Property(property: 'sexo', type: 'string', enum: ['femenino', 'masculino', 'otro'], nullable: true),
        new OA\Property(property: 'tipoDiabetes', type: 'string', maxLength: 255),
        new OA\Property(property: 'pesoKg', type: 'number', minimum: 1, maximum: 400, nullable: true),
        new OA\Property(property: 'tallaCm', type: 'integer', minimum: 30, maximum: 260, nullable: true),
        new OA\Property(property: 'diagnosticadoEn', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'alergias', type: 'string', nullable: true),
        new OA\Property(property: 'comorbilidades', type: 'string', nullable: true),
        new OA\Property(property: 'tabaquismo', type: 'boolean'),
        new OA\Property(property: 'contactoEmergencia', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
    ],
)]
#[OA\Schema(
    schema: 'PacienteCrear',
    type: 'object',
    required: ['usuarioId', 'fechaNacimiento', 'tipoDiabetes'],
    properties: [
        new OA\Property(property: 'usuarioId', type: 'integer'),
        new OA\Property(property: 'usuario', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'clinicaId', type: 'integer', nullable: true),
        new OA\Property(property: 'fechaNacimiento', type: 'string', format: 'date'),
        new OA\Property(property: 'sexo', type: 'string', enum: ['femenino', 'masculino', 'otro'], nullable: true),
        new OA\Property(property: 'tipoDiabetes', type: 'string', maxLength: 255),
        new OA\Property(property: 'pesoKg', type: 'number', minimum: 1, maximum: 400, nullable: true),
        new OA\Property(property: 'tallaCm', type: 'integer', minimum: 30, maximum: 260, nullable: true),
        new OA\Property(property: 'diagnosticadoEn', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'alergias', type: 'string', nullable: true),
        new OA\Property(property: 'comorbilidades', type: 'string', nullable: true),
        new OA\Property(property: 'tabaquismo', type: 'boolean'),
        new OA\Property(property: 'contactoEmergencia', type: 'string', maxLength: 255, nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'PacienteActualizar',
    type: 'object',
    properties: [
        new OA\Property(property: 'usuarioId', type: 'integer'),
        new OA\Property(property: 'usuario', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'clinicaId', type: 'integer', nullable: true),
        new OA\Property(property: 'fechaNacimiento', type: 'string', format: 'date'),
        new OA\Property(property: 'sexo', type: 'string', enum: ['femenino', 'masculino', 'otro'], nullable: true),
        new OA\Property(property: 'tipoDiabetes', type: 'string', maxLength: 255),
        new OA\Property(property: 'pesoKg', type: 'number', minimum: 1, maximum: 400, nullable: true),
        new OA\Property(property: 'tallaCm', type: 'integer', minimum: 30, maximum: 260, nullable: true),
        new OA\Property(property: 'diagnosticadoEn', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'alergias', type: 'string', nullable: true),
        new OA\Property(property: 'comorbilidades', type: 'string', nullable: true),
        new OA\Property(property: 'tabaquismo', type: 'boolean'),
        new OA\Property(property: 'contactoEmergencia', type: 'string', maxLength: 255, nullable: true),
    ],
)]
final class PacienteDocs
{
    #[OA\Get(
        path: '/pacientes',
        tags: ['Paciente'],
        summary: 'Listar Paciente',
        description: 'Paginado. Filtra por columnas exactas via query string y por rango de creacion (desde/hasta). Solo devuelve lo que esta dentro del alcance del usuario.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/pagina'),
            new OA\Parameter(ref: '#/components/parameters/porPagina'),
            new OA\Parameter(ref: '#/components/parameters/desde'),
            new OA\Parameter(ref: '#/components/parameters/hasta'),
            new OA\Parameter(name: 'orden', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id', 'created_at']), description: 'Columna de orden'),
            new OA\Parameter(ref: '#/components/parameters/direccion'),
            new OA\Parameter(name: 'clinicaId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por clinicaId'),
            new OA\Parameter(name: 'tipoDiabetes', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 255), description: 'Filtro exacto por tipoDiabetes'),
            new OA\Parameter(name: 'sexo', in: 'query', schema: new OA\Schema(type: 'string', enum: ['femenino', 'masculino', 'otro']), description: 'Filtro exacto por sexo'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pagina de Paciente', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Paginado'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Paciente'))])])),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
        ],
    )]
    public function getListar(): void {}

    #[OA\Post(
        path: '/pacientes',
        tags: ['Paciente'],
        summary: 'Crear Paciente',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PacienteCrear')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/Paciente')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function postCrear(): void {}

    #[OA\Get(
        path: '/pacientes/{id}',
        tags: ['Paciente'],
        summary: 'Ver Paciente',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Paciente', content: new OA\JsonContent(ref: '#/components/schemas/Paciente')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    public function getVer(): void {}

    #[OA\Put(
        path: '/pacientes/{id}',
        tags: ['Paciente'],
        summary: 'Actualizar Paciente',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PacienteActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Paciente')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function putActualizar(): void {}

    #[OA\Patch(
        path: '/pacientes/{id}',
        tags: ['Paciente'],
        summary: 'Actualizar Paciente',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PacienteActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Paciente')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function patchActualizar(): void {}

    #[OA\Delete(
        path: '/pacientes/{id}',
        tags: ['Paciente'],
        summary: 'Eliminar Paciente',
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
