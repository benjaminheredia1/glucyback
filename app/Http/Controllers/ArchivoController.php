<?php

namespace App\Http\Controllers;

use App\Models\Archivo;
use App\Models\EstudioMedico;
use App\Models\User;
use App\Support\AlmacenArchivos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
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

        $archivo = $almacen->guardar(
            $request->file('archivo'),
            $usuario,
            $request->input('nombre'),
            $request->input('descripcion'),
        );

        $this->auditar($request, 'subir', $archivo, null, $archivo->toArray());

        return response()->json($archivo, $archivo->wasRecentlyCreated ? 201 : 200);
    }

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
