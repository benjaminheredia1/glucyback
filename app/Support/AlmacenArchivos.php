<?php

namespace App\Support;

use App\Models\Archivo;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Guarda estudios y adjuntos en el disco privado.
 *
 * El nombre original nunca se usa como ruta: se conserva aparte, en la columna
 * `nombre`, y en disco el archivo vive bajo un ULID. Asi un nombre con "../" o
 * con unicode raro no puede tocar el arbol de directorios.
 */
class AlmacenArchivos
{
    public const DISCO = 'medico';

    public const MAX_KB = 10240;

    /** @var array<string> */
    public const MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/heic',
        'image/heif',
        'image/webp',
    ];

    public function guardar(UploadedFile $subido, User $usuario, ?string $nombre = null, ?string $descripcion = null): Archivo
    {
        $hash = hash_file('sha256', $subido->getRealPath());

        // Volver a subir el mismo archivo (por ejemplo tras un rechazo) reutiliza
        // la fila en vez de duplicar bytes en disco.
        $existente = Archivo::where('usuarioId', $usuario->id)
            ->where('hashSha256', $hash)
            ->first();

        if ($existente !== null) {
            return $existente;
        }

        $extension = strtolower($subido->extension() ?: $subido->getClientOriginalExtension() ?: 'bin');
        $ruta = 'estudios/'.now()->format('Y/m').'/'.Str::ulid().'.'.$extension;

        Storage::disk(self::DISCO)->put($ruta, $subido->get(), 'private');

        return Archivo::create([
            'nombre' => $nombre ?: $subido->getClientOriginalName(),
            'descripcion' => $descripcion,
            'ruta' => $ruta,
            'disk' => self::DISCO,
            'mime' => $subido->getMimeType(),
            'sizeBytes' => $subido->getSize(),
            'hashSha256' => $hash,
            'esPrivado' => true,
            'usuarioId' => $usuario->id,
        ]);
    }

    public function existe(Archivo $archivo): bool
    {
        return Storage::disk($archivo->disk)->exists($archivo->ruta);
    }

    /**
     * Reglas de validacion del campo subido.
     *
     * @return array<int, string>
     */
    public static function reglasDeArchivo(): array
    {
        return [
            'file',
            'max:'.self::MAX_KB,
            'mimetypes:'.implode(',', self::MIMES),
        ];
    }
}
