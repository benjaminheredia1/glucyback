<?php

namespace App\Models;

use App\Support\Alcance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Archivo extends Model
{
    use SoftDeletes;

    protected $table = 'archivos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'ruta',
        'disk',
        'mime',
        'sizeBytes',
        'hashSha256',
        'esPrivado',
        'usuarioId',
    ];

    protected function casts(): array
    {
        return [
            'esPrivado' => 'boolean',
            'sizeBytes' => 'integer',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuarioId');
    }

    /**
     * Archivos que un usuario puede leer o referenciar.
     *
     * El paciente ve solo los suyos; el doctor tambien los adjuntos a estudios de
     * sus pacientes, que es lo que necesita para validarlos.
     *
     * Vive en el modelo porque la regla la usan dos lugares: el alcance de lectura
     * de ArchivoController y la comprobacion de `archivoId` en las entidades que
     * adjuntan un archivo.
     */
    public function scopeVisiblePara(Builder $consulta, ?User $usuario): Builder
    {
        if ($usuario === null || $usuario->esAdmin()) {
            return $consulta;
        }

        if ($usuario->esPaciente()) {
            return $consulta->where('usuarioId', $usuario->id);
        }

        $pacientes = Alcance::pacientesVisibles($usuario) ?? [];

        return $consulta->where(function (Builder $q) use ($usuario, $pacientes) {
            $q->where('usuarioId', $usuario->id)
                ->orWhereExists(function ($sub) use ($pacientes) {
                    $sub->selectRaw('1')
                        ->from('estudios_medicos')
                        ->whereColumn('estudios_medicos.archivoId', 'archivos.id')
                        ->whereIn('estudios_medicos.pacienteId', $pacientes);
                });
        });
    }

    public static function exigirVisible(?int $archivoId, ?User $usuario): void
    {
        if ($archivoId === null) {
            return;
        }

        abort_unless(
            self::query()->visiblePara($usuario)->whereKey($archivoId)->exists(),
            403,
            'El archivo referenciado esta fuera de tu alcance.'
        );
    }
}
