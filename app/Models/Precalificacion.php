<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Precalificacion extends Model
{
    protected $table = 'precalificaciones';

    protected $fillable = [
        'pacienteId',
        'leadEmail',
        'resultado',
        'motivo',
        'versionCuestionario',
        'respondidoEn',
    ];

    protected function casts(): array
    {
        return [
            'respondidoEn' => 'datetime',
            'versionCuestionario' => 'integer',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'pacienteId');
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(PrecalificacionRespuesta::class, 'precalificacionId');
    }
}
