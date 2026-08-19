<?php

// Generado por `php artisan openapi:crud`. No editar a mano.

namespace App\OpenApi\Crud;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Solicitud Acceso Doctor', description: 'Lectura: admin. Escritura: admin.')]
#[OA\Schema(
    schema: 'SolicitudAccesoDoctor',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', readOnly: true),
        new OA\Property(property: 'nombre', type: 'string', maxLength: 255),
        new OA\Property(property: 'matricula', type: 'string', maxLength: 100),
        new OA\Property(property: 'especialidad', type: 'string', maxLength: 100),
        new OA\Property(property: 'correo', type: 'string', format: 'email', maxLength: 255),
        new OA\Property(property: 'institucion', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'estado', type: 'string', enum: ['pendiente', 'aprobada', 'rechazada']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),
    ],
)]
#[OA\Schema(
    schema: 'SolicitudAccesoDoctorCrear',
    type: 'object',
    required: ['nombre', 'matricula', 'especialidad', 'correo'],
    properties: [
        new OA\Property(property: 'nombre', type: 'string', maxLength: 255),
        new OA\Property(property: 'matricula', type: 'string', maxLength: 100),
        new OA\Property(property: 'especialidad', type: 'string', maxLength: 100),
        new OA\Property(property: 'correo', type: 'string', format: 'email', maxLength: 255),
        new OA\Property(property: 'institucion', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'estado', type: 'string', enum: ['pendiente', 'aprobada', 'rechazada']),
    ],
)]
#[OA\Schema(
    schema: 'SolicitudAccesoDoctorActualizar',
    type: 'object',
    properties: [
        new OA\Property(property: 'nombre', type: 'string', maxLength: 255),
        new OA\Property(property: 'matricula', type: 'string', maxLength: 100),
        new OA\Property(property: 'especialidad', type: 'string', maxLength: 100),
        new OA\Property(property: 'correo', type: 'string', format: 'email', maxLength: 255),
        new OA\Property(property: 'institucion', type: 'string', maxLength: 255, nullable: true),
        new OA\Property(property: 'estado', type: 'string', enum: ['pendiente', 'aprobada', 'rechazada']),
    ],
)]
final class SolicitudAccesoDoctorDocs
{
    #[OA\Get(
        path: '/solicitudes-acceso-doctor',
        tags: ['Solicitud Acceso Doctor'],
        summary: 'Listar Solicitud Acceso Doctor',
        description: 'Paginado. Filtra por columnas exactas via query string y por rango de creacion (desde/hasta). Solo devuelve lo que esta dentro del alcance del usuario.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/pagina'),
            new OA\Parameter(ref: '#/components/parameters/porPagina'),
            new OA\Parameter(ref: '#/components/parameters/desde'),
            new OA\Parameter(ref: '#/components/parameters/hasta'),
            new OA\Parameter(name: 'orden', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id', 'nombre', 'estado', 'created_at']), description: 'Columna de orden'),
            new OA\Parameter(ref: '#/components/parameters/direccion'),
            new OA\Parameter(name: 'estado', in: 'query', schema: new OA\Schema(type: 'string', enum: ['pendiente', 'aprobada', 'rechazada']), description: 'Filtro exacto por estado'),
            new OA\Parameter(name: 'especialidad', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 100), description: 'Filtro exacto por especialidad'),
            new OA\Parameter(name: 'correo', in: 'query', schema: new OA\Schema(type: 'string', format: 'email', maxLength: 255), description: 'Filtro exacto por correo'),
            new OA\Parameter(name: 'matricula', in: 'query', schema: new OA\Schema(type: 'string', maxLength: 100), description: 'Filtro exacto por matricula'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pagina de Solicitud Acceso Doctor', content: new OA\JsonContent(allOf: [new OA\Schema(ref: '#/components/schemas/Paginado'), new OA\Schema(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/SolicitudAccesoDoctor'))])])),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
        ],
    )]
    public function getListar(): void {}

    #[OA\Post(
        path: '/solicitudes-acceso-doctor',
        tags: ['Solicitud Acceso Doctor'],
        summary: 'Crear Solicitud Acceso Doctor',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/SolicitudAccesoDoctorCrear')),
        responses: [
            new OA\Response(response: 201, description: 'Creado', content: new OA\JsonContent(ref: '#/components/schemas/SolicitudAccesoDoctor')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function postCrear(): void {}

    #[OA\Get(
        path: '/solicitudes-acceso-doctor/{id}',
        tags: ['Solicitud Acceso Doctor'],
        summary: 'Ver Solicitud Acceso Doctor',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Solicitud Acceso Doctor', content: new OA\JsonContent(ref: '#/components/schemas/SolicitudAccesoDoctor')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    public function getVer(): void {}

    #[OA\Put(
        path: '/solicitudes-acceso-doctor/{id}',
        tags: ['Solicitud Acceso Doctor'],
        summary: 'Actualizar Solicitud Acceso Doctor',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/SolicitudAccesoDoctorActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/SolicitudAccesoDoctor')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function putActualizar(): void {}

    #[OA\Patch(
        path: '/solicitudes-acceso-doctor/{id}',
        tags: ['Solicitud Acceso Doctor'],
        summary: 'Actualizar Solicitud Acceso Doctor',
        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/SolicitudAccesoDoctorActualizar')),
        responses: [
            new OA\Response(response: 200, description: 'Actualizado', content: new OA\JsonContent(ref: '#/components/schemas/SolicitudAccesoDoctor')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function patchActualizar(): void {}

    #[OA\Delete(
        path: '/solicitudes-acceso-doctor/{id}',
        tags: ['Solicitud Acceso Doctor'],
        summary: 'Eliminar Solicitud Acceso Doctor',
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
