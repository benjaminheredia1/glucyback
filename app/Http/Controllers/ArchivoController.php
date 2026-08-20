<?php

namespace App\Http\Controllers;

use App\Models\Archivo;
use App\Models\EstudioMedico;
use App\Models\User;
use App\Support\AlmacenArchivos;
use App\Support\ValidacionEstudiosIa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArchivoController extends BaseCrudController
{
    /** Minutos de vida de un enlace firmado. Corto a proposito: ver `enlace()`. */
    public const MINUTOS_ENLACE = 5;

    protected string $modelo = Archivo::class;

    // Un archivo entra al sistema solo por `subir()`. `crear()` queda para el
    // admin, porque registrar una ruta a mano se salta la validacion de tipo,
    // el calculo de hash y el guardado en el disco privado.
    protected array $rolesEscritura = [User::ROL_ADMIN];

    protected array $filtrables = ['usuarioId', 'mime'];

    protected array $ordenables = ['id', 'created_at', 'sizeBytes'];

    protected function aplicarAlcance(Builder $consulta, ?User $usuario): void
    {
        $consulta->visiblePara($usuario);
    }

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'nombre' => [$req, 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'ruta' => [$req, 'string', 'max:2048'],
            'disk' => ['sometimes', 'string', 'max:50'],
            'mime' => ['nullable', 'string', 'max:120'],
            'sizeBytes' => ['nullable', 'integer', 'min:0'],
            'hashSha256' => ['nullable', 'string', 'size:64'],
            'esPrivado' => ['sometimes', 'boolean'],
            'usuarioId' => ['nullable', 'exists:users,id'],
        ];
    }

    /** Un archivo adjunto a un estudio es evidencia clinica y no se borra. */
    protected function antesDeEliminar(Request $request, Model $registro): void
    {
        abort_if(
            EstudioMedico::withTrashed()->where('archivoId', $registro->getKey())->exists(),
            409,
            'El archivo respalda un estudio medico y no se puede eliminar.'
        );
    }

    #[OA\Post(
        path: '/archivos/subir',
        tags: ['Archivo'],
        summary: 'Subir un archivo',
        description: 'Cualquier rol puede subir; el archivo queda a nombre del usuario. Si ya existia uno identico (mismo hash) responde 200 con el existente.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(
            required: ['archivo'],
            properties: [
                new OA\Property(property: 'archivo', type: 'string', format: 'binary'),
                new OA\Property(property: 'nombre', type: 'string', maxLength: 255, nullable: true),
                new OA\Property(property: 'descripcion', type: 'string', maxLength: 255, nullable: true),
            ],
        ))),
        responses: [
            new OA\Response(response: 201, description: 'Archivo guardado. `estudiosAprobados` trae los estudios que la IA detecto y aprobo al momento en este archivo (vacio si la IA no corrio o no detecto ninguno).', content: new OA\JsonContent(allOf: [
                new OA\Schema(ref: '#/components/schemas/Archivo'),
                new OA\Schema(properties: [
                    new OA\Property(property: 'estudiosAprobados', type: 'array', items: new OA\Items(ref: '#/components/schemas/EstudioMedico')),
                ]),
            ])),
            new OA\Response(response: 200, description: 'Archivo identico ya existente. Incluye `estudiosAprobados` igual que el 201.', content: new OA\JsonContent(allOf: [
                new OA\Schema(ref: '#/components/schemas/Archivo'),
                new OA\Schema(properties: [
                    new OA\Property(property: 'estudiosAprobados', type: 'array', items: new OA\Items(ref: '#/components/schemas/EstudioMedico')),
                ]),
            ])),
            new OA\Response(response: 422, description: 'Datos invalidos, o la IA determino que el archivo no es un estudio medico legible', content: new OA\JsonContent(ref: '#/components/schemas/ErrorValidacion')),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 503, description: 'La validacion automatica por IA no esta disponible; reintentar en unos minutos', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    /**
     * Subida real. Cualquier rol puede subir; el archivo queda a su nombre.
     */
    public function subir(Request $request, AlmacenArchivos $almacen): JsonResponse
    {
        $usuario = $request->user();

        $request->validate([
            'archivo' => ['required', ...AlmacenArchivos::reglasDeArchivo()],
            'nombre' => ['nullable', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);

        // El archivo de un paciente pasa primero por la IA, que decide a que
        // tipo de estudio corresponde y si es valido: no hay revision humana
        // de estudios. Si la IA no puede analizar (proveedor caido, sin API
        // key), la subida falla con 503 en vez de dejar el estudio pendiente.
        $analisis = null;
        $paciente = $usuario->paciente;

        if (config('ai.validar_estudios') && $usuario->esPaciente() && $paciente !== null) {
            $analisis = app(ValidacionEstudiosIa::class)->analizar($request->file('archivo'));

            abort_if(
                $analisis === null,
                503,
                'La validacion automatica de estudios no esta disponible en este momento. Intenta de nuevo en unos minutos.'
            );

            abort_if(
                ! $analisis['esEstudioValido'],
                422,
                $analisis['motivo'] ?? 'El archivo no parece un estudio medico legible.'
            );
        }

        $archivo = $almacen->guardar(
            $request->file('archivo'),
            $usuario,
            $request->input('nombre'),
            $request->input('descripcion'),
        );

        $this->auditar($request, 'subir', $archivo, null, $archivo->toArray());

        // La respuesta lleva el veredicto inmediato: `estudiosAprobados` son
        // los estudios que la IA detecto y aprobo en este mismo archivo, para
        // que el cliente no registre duplicados pendientes de esos tipos.
        $aprobados = [];

        if ($analisis !== null) {
            $aprobados = app(ValidacionEstudiosIa::class)->aprobar($archivo, $paciente, $analisis['estudiosDetectados']);
        }

        return response()->json(
            $archivo->toArray() + [
                'estudiosAprobados' => array_map(
                    fn (EstudioMedico $estudio) => $estudio->load('tipoEstudio')->toArray(),
                    $aprobados,
                ),
            ],
            $archivo->wasRecentlyCreated ? 201 : 200,
        );
    }

    #[OA\Get(
        path: '/archivos/{id}/descargar',
        tags: ['Archivo'],
        summary: 'Descargar un archivo (autenticado)',
        description: 'Devuelve los bytes como adjunto. Camino normal de la app: manda el Bearer y recibe el archivo.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Contenido del archivo', content: new OA\MediaType(mediaType: 'application/octet-stream', schema: new OA\Schema(type: 'string', format: 'binary'))),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    /**
     * Descarga autenticada. Es el camino normal: la app manda el Bearer y recibe
     * los bytes. No expone ninguna URL reutilizable.
     */
    public function descargar(Request $request, int $id): StreamedResponse
    {
        $this->autorizarLectura($request);

        $archivo = $this->consulta($request)->findOrFail($id);

        abort_unless(
            Storage::disk($archivo->disk)->exists($archivo->ruta),
            404,
            'El archivo ya no esta disponible en el almacenamiento.'
        );

        $this->auditar($request, 'descargar', $archivo, null, null);

        return Storage::disk($archivo->disk)->download($archivo->ruta, $archivo->nombre);
    }

    #[OA\Post(
        path: '/archivos/{id}/enlace',
        tags: ['Archivo'],
        summary: 'Emitir enlace temporal firmado',
        description: 'Para contextos que no pueden mandar cabeceras (<Image src>, visor PDF). Vale 5 minutos por si solo: quien lo tenga abre el archivo. La emision queda auditada.',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(ref: '#/components/parameters/id')],
        responses: [
            new OA\Response(response: 200, description: 'Enlace emitido', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'url', type: 'string', format: 'uri'),
                new OA\Property(property: 'expiraEn', type: 'string', format: 'date-time'),
            ])),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
            new OA\Response(response: 403, ref: '#/components/responses/NoAutorizado'),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    /**
     * Enlace temporal firmado, para contextos que no pueden mandar cabeceras
     * (un <Image src> en la app, un visor de PDF embebido).
     *
     * Compromiso deliberado: durante esos minutos el enlace vale por si solo,
     * asi que quien lo tenga puede abrir el archivo. Por eso la vida es corta,
     * la emision exige sesion y pasa por el mismo alcance que la descarga, y
     * queda registrada en la bitacora.
     */
    public function enlace(Request $request, int $id): JsonResponse
    {
        $this->autorizarLectura($request);

        $archivo = $this->consulta($request)->findOrFail($id);

        $expiraEn = now()->addMinutes(self::MINUTOS_ENLACE);

        $this->auditar($request, 'emitir-enlace', $archivo, null, ['expiraEn' => $expiraEn->toIso8601String()]);

        return response()->json([
            'url' => URL::temporarySignedRoute('archivos.firmado', $expiraEn, ['id' => $archivo->id]),
            'expiraEn' => $expiraEn->toIso8601String(),
        ]);
    }

    #[OA\Get(
        path: '/archivos/firmado/{id}',
        tags: ['Archivo'],
        summary: 'Servir archivo por enlace firmado (publico)',
        description: 'Destino de POST /archivos/{id}/enlace. La firma de la URL (query signature + expires) es la credencial; no requiere Bearer. Limite: 60 por minuto.',
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/id'),
            new OA\Parameter(name: 'expires', in: 'query', required: true, schema: new OA\Schema(type: 'integer'), description: 'Unix timestamp de expiracion (lo pone el enlace)'),
            new OA\Parameter(name: 'signature', in: 'query', required: true, schema: new OA\Schema(type: 'string'), description: 'Firma HMAC (la pone el enlace)'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Contenido del archivo', content: new OA\MediaType(mediaType: 'application/octet-stream', schema: new OA\Schema(type: 'string', format: 'binary'))),
            new OA\Response(response: 403, description: 'Firma invalida o vencida', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
        ],
    )]
    /**
     * Destino del enlace firmado. La firma es la credencial: si no valida, el
     * middleware `signed` corta antes de llegar aca.
     */
    public function servirFirmado(int $id): StreamedResponse
    {
        $archivo = Archivo::findOrFail($id);

        abort_unless(
            Storage::disk($archivo->disk)->exists($archivo->ruta),
            404,
            'El archivo ya no esta disponible en el almacenamiento.'
        );

        return Storage::disk($archivo->disk)->response($archivo->ruta, $archivo->nombre);
    }
}
