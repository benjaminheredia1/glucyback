<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PreguntaPrecalificacion extends Model
{
    protected $table = 'preguntas_precalificacion';

    protected $fillable = [
        'codigo',
        'texto',
        'respuestaAlarma',
        'esUrgente',
        'motivo',
        'orden',
        'version',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'esUrgente' => 'boolean',
            'activa' => 'boolean',
            'orden' => 'integer',
            'version' => 'integer',
        ];
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(PrecalificacionRespuesta::class, 'preguntaId');
    }
}
