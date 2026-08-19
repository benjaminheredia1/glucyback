<?php

// Generado por `php artisan openapi:crud`. No editar a mano.

namespace App\OpenApi\Crud;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Doctor', description: 'Lectura: admin, doctor, paciente. Escritura: admin.')]
#[OA\Schema(
    schema: 'Doctor',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', readOnly: true),
        new OA\Property(property: 'usuarioId', type: 'integer'),
        new OA\Property(property: 'clinicaId', type: 'integer'),
        new OA\Property(property: 'matricula', type: 'string', maxLength: 255),
        new OA\Property(property: 'especialidad', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'firmaArchivoId', type: 'integer', nullable: true),
        new OA\Property(property: 'estado', type: 'string', enum: ['activo', 'inactivo']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
    ],
)]
#[OA\Schema(
    schema: 'DoctorCrear',
    type: 'object',
    required: ['usuarioId', 'clinicaId', 'matricula'],
    properties: [
        new OA\Property(property: 'usuarioId', type: 'integer'),
        new OA\Property(property: 'clinicaId', type: 'integer'),
        new OA\Property(property: 'matricula', type: 'string', maxLength: 255),
        new OA\Property(property: 'especialidad', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'firmaArchivoId', type: 'integer', nullable: true),
        new OA\Property(property: 'estado', type: 'string', enum: ['activo', 'inactivo']),
    ],
)]
#[OA\Schema(
    schema: 'DoctorActualizar',
    type: 'object',
    properties: [
        new OA\Property(property: 'usuarioId', type: 'integer'),
        new OA\Property(property: 'clinicaId', type: 'integer'),
        new OA\Property(property: 'matricula', type: 'string', maxLength: 255),
        new OA\Property(property: 'especialidad', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'firmaArchivoId', type: 'integer', nullable: true),
        new OA\Property(property: 'estado', type: 'string', enum: ['activo', 'inactivo']),
    ],
)]
final class DoctorDocs
{
    #[OA\Get(
        path: '/doctores',
        tags: ['Doctor'],
        summary: 'Listar Doctor',
        description: 'Paginado. Filtra por columnas exactas via query string y por rango de creacion (desde/hasta). Solo devuelve lo que esta dentro del alcance del usuario.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/pagina'),
            new OA\Parameter(ref: '#/components/parameters/porPagina'),
            new OA\Parameter(ref: '#/components/parameters/desde'),
            new OA\Parameter(ref: '#/components/parameters/hasta'),
            new OA\Parameter(name: 'orden', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id', 'matricula', 'created_at']), description: 'Columna de orden'),
            new OA\Parameter(ref: '#/components/parameters/direccion'),
            new OA\Parameter(name: 'clinicaId', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Filtro exacto por clinicaId'),
            new OA\Parameter(name: 'estado', in: 'query', schema: new OA\Schema(type: 'string', enum: ['activo', 'inactivo']), description: 'Filtro exacto por estado'),
            new OA\Parameter(name: 'especialidad', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 255), description: 'Filtro exacto por especialidad'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pagina de Doctor', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Paginado'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Doctor'))])])),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
        ],
    )]
    public function getListar(): void {}

    #[OA\Post(
        path: '/doctores',
        tags: ['Doctor'],
        summary: 'Crear Doctor',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/DoctorCrear')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/Doctor')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function postCrear(): void {}

    #[OA\Get(
        path: '/doctores/{id}',
        tags: ['Doctor'],
        summary: 'Ver Doctor',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Doctor', content: new OA\JsonContent(ref: '#/components/schemas/Doctor')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    public function getVer(): void {}

    #[OA\Put(
        path: '/doctores/{id}',
        tags: ['Doctor'],
        summary: 'Actualizar Doctor',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/DoctorActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Doctor')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function putActualizar(): void {}

    #[OA\Patch(
        path: '/doctores/{id}',
        tags: ['Doctor'],
        summary: 'Actualizar Doctor',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/DoctorActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/Doctor')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function patchActualizar(): void {}

    #[OA\Delete(
        path: '/doctores/{id}',
        tags: ['Doctor'],
        summary: 'Eliminar Doctor',
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
