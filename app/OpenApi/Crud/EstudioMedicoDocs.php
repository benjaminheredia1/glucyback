<?php

// Generado por `php artisan openapi:crud`. No editar a mano.

namespace App\OpenApi\Crud;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Estudio Medico', description: 'Lectura: admin, doctor, paciente. Escritura: admin, doctor, paciente.')]
#[OA\Schema(
    schema: 'EstudioMedico',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', readOnly: true),
        new OA\Property(property: 'tipoEstudioId', type: 'integer'),
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'archivoId', type: 'integer', nullable: true),
        new OA\Property(property: 'descripcion', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'fecha', type: 'string', format: 'date'),
        new OA\Property(property: 'valor', type: 'number', nullable: true),
        new OA\Property(property: 'unidad', type: 'string', maxLength: 50, nullable: true),
        new OA\Property(property: 'origen', type: 'string', enum: ['carga', 'laboratorio']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
    ],
)]
#[OA\Schema(
    schema: 'EstudioMedicoCrear',
    type: 'object',
    required: ['tipoEstudioId', 'pacienteId', 'fecha'],
    properties: [
        new OA\Property(property: 'tipoEstudioId', type: 'integer'),
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'archivoId', type: 'integer', nullable: true),
        new OA\Property(property: 'descripcion', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'fecha', type: 'string', format: 'date'),
        new OA\Property(property: 'valor', type: 'number', nullable: true),
        new OA\Property(property: 'unidad', type: 'string', maxLength: 50, nullable: true),
        new OA\Property(property: 'origen', type: 'string', enum: ['carga', 'laboratorio']),
    ],
)]
#[OA\Schema(
    schema: 'EstudioMedicoActualizar',
    type: 'object',
    properties: [
        new OA\Property(property: 'tipoEstudioId', type: 'integer'),
        new OA\Property(property: 'pacienteId', type: 'integer'),
        new OA\Property(property: 'archivoId', type: 'integer', nullable: true),
        new OA\Property(property: 'descripcion', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'fecha', type: 'string', format: 'date'),
        new OA\Property(property: 'valor', type: 'number', nullable: true),
        new OA\Property(property: 'unidad', type: 'string', maxLength: 50, nullable: true),
        new OA\Property(property: 'origen', type: 'string', enum: ['carga', 'laboratorio']),
    ],
)]
final class EstudioMedicoDocs
{
    #[OA\Get(
        path: '/estudios-medicos',
        tags: ['Estudio Medico'],
        summary: 'Listar Estudio Medico',
        description: 'Paginado. Filtra por columnas exactas via query string y por rango de creacion (desde/hasta). Solo devuelve lo que esta dentro del alcance del usuario.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/pagina'),
            new OA\Parameter(ref: '#/components/parameters/porPagina'),
            new OA\Parameter(ref: '#/components/parameters/desde'),
            new OA\Parameter(ref: '#/components/parameters/hasta'),
            new OA\Parameter(name: 'orden', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id', 'fecha', 'created_at']), description: 'Columna de orden'),
            new OA\Parameter(ref: '#/components/parameters/direccion'),
            new OA\Parameter(name: 'pacienteId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por pacienteId'),
            new OA\Parameter(name: 'tipoEstudioId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por tipoEstudioId'),
            new OA\Parameter(name: 'estado', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Filtro exacto por estado'),
            new OA\Parameter(name: 'origen', in: 'query', schema: new OA\Schema(type: 'string', enum: ['carga', 'laboratorio']), description: 'Filtro exacto por origen'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pagina de Estudio Medico', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Paginado'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/EstudioMedico'))])])),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
        ],
    )]
    public function getListar(): void {}

    #[OA\Post(
        path: '/estudios-medicos',
        tags: ['Estudio Medico'],
        summary: 'Crear Estudio Medico',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/EstudioMedicoCrear')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/EstudioMedico')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function postCrear(): void {}

    #[OA\Get(
        path: '/estudios-medicos/{id}',
        tags: ['Estudio Medico'],
        summary: 'Ver Estudio Medico',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Estudio Medico', content: new OA\JsonContent(ref: '#/components/schemas/EstudioMedico')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    public function getVer(): void {}

    #[OA\Put(
        path: '/estudios-medicos/{id}',
        tags: ['Estudio Medico'],
        summary: 'Actualizar Estudio Medico',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/EstudioMedicoActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/EstudioMedico')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function putActualizar(): void {}

    #[OA\Patch(
        path: '/estudios-medicos/{id}',
        tags: ['Estudio Medico'],
        summary: 'Actualizar Estudio Medico',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/EstudioMedicoActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/EstudioMedico')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function patchActualizar(): void {}

    #[OA\Delete(
        path: '/estudios-medicos/{id}',
        tags: ['Estudio Medico'],
        summary: 'Eliminar Estudio Medico',
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
