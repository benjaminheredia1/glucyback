<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Raiz del documento OpenAPI: metadatos, servidor, autenticacion y esquemas
 * compartidos. Las operaciones viven en cada controlador (acciones propias)
 * y en app/OpenApi/Crud (generadas con `php artisan openapi:crud`).
 *
 * Regenerar: `php artisan l5-swagger:generate`. UI: /api/documentation.
 */
#[OA\Info(
    version: '1.0.0',
    title: 'Glucy API',
    description: 'API backend de Glucy. Salvo las rutas publicas, todo exige `Authorization: Bearer <token>` '
        .'(token de Sanctum obtenido en /auth/auth0, /auth/panel o /auth/anonimo). '
        .'Un recurso fuera del alcance del usuario responde 404, no 403.',
)]
#[OA\Server(url: \L5_SWAGGER_CONST_HOST.'/api', description: 'Servidor actual')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    description: 'Token de Sanctum. Se manda como `Authorization: Bearer <token>`.',
)]
#[OA\Tag(name: 'Auth', description: 'Inicio y cierre de sesion')]
#[OA\Tag(name: 'Perfil', description: 'Datos del usuario autenticado')]
#[OA\Tag(name: 'Actividad', description: 'Historial del paciente para la app')]
#[OA\Response(response: 'NoAutenticado', description: 'Sin token o token invalido', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[OA\Response(response: 'NoAutorizado', description: 'El rol del usuario no puede hacer esta accion', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[OA\Response(response: 'NoEncontrado', description: 'No existe o esta fuera del alcance del usuario', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[OA\Response(response: 'Conflicto', description: 'El estado actual del recurso no permite la accion', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[OA\Response(response: 'Validacion', description: 'Datos invalidos', content: new OA\JsonContent(ref: '#/components/schemas/ErrorValidacion'))]
#[OA\Parameter(parameter: 'id', name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'Identificador del recurso')]
#[OA\Parameter(parameter: 'pagina', name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1), description: 'Numero de pagina')]
#[OA\Parameter(parameter: 'porPagina', name: 'porPagina', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100), description: 'Registros por pagina (max 100)')]
#[OA\Parameter(parameter: 'desde', name: 'desde', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'), description: 'Creados desde esta fecha (YYYY-MM-DD)')]
#[OA\Parameter(parameter: 'hasta', name: 'hasta', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'), description: 'Creados hasta esta fecha (YYYY-MM-DD)')]
#[OA\Parameter(parameter: 'direccion', name: 'direccion', in: 'query', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc']), description: 'Direccion del orden')] final class OpenApi
{
    #[OA\Schema(
        schema: 'Error',
        type: 'object',
        properties: [new OA\Property(property: 'message', type: 'string', example: 'No autorizado para este recurso.')],
    )]
    public function schemaError(): void {}

    #[OA\Schema(
        schema: 'ErrorValidacion',
        type: 'object',
        properties: [
            new OA\Property(property: 'message', type: 'string', example: 'El campo nombre es obligatorio.'),
            new OA\Property(
                property: 'errors',
                type: 'object',
                additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string')),
            ),
        ],
    )]
    public function schemaErrorValidacion(): void {}

    #[OA\Schema(
        schema: 'Paginado',
        type: 'object',
        description: 'Envoltura estandar de paginacion de Laravel. `data` lleva los registros.',
        properties: [
            new OA\Property(property: 'current_page', type: 'integer', example: 1),
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
            new OA\Property(property: 'first_page_url', type: 'string'),
            new OA\Property(property: 'from', type: 'integer', nullable: true),
            new OA\Property(property: 'last_page', type: 'integer'),
            new OA\Property(property: 'last_page_url', type: 'string'),
            new OA\Property(property: 'next_page_url', type: 'string', nullable: true),
            new OA\Property(property: 'path', type: 'string'),
            new OA\Property(property: 'per_page', type: 'integer', example: 25),
            new OA\Property(property: 'prev_page_url', type: 'string', nullable: true),
            new OA\Property(property: 'to', type: 'integer', nullable: true),
            new OA\Property(property: 'total', type: 'integer'),
        ],
    )]
    public function schemaPaginado(): void {}

    #[OA\Schema(
        schema: 'SesionIniciada',
        type: 'object',
        properties: [
            new OA\Property(property: 'token', type: 'string', description: 'Bearer de Sanctum', example: '1|abc123...'),
            new OA\Property(property: 'usuario', type: 'object', description: 'Usuario con doctor.clinica y paciente cargados'),
        ],
    )]
    public function schemaSesionIniciada(): void {}

    // GET /user es un closure en routes/api.php: se documenta aqui.
    #[OA\Get(
        path: '/user',
        tags: ['Perfil'],
        summary: 'Usuario autenticado',
        description: 'Devuelve el usuario de la sesion con doctor.clinica y paciente cargados.',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Usuario', content: new OA\JsonContent(type: 'object')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
        ],
    )]
    public function usuarioActual(): void {}
}
