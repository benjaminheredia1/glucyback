<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrecalificacionRespuesta extends Model
{
    protected $table = 'precalificacion_respuestas';

    protected $fillable = [
        'precalificacionId',
        'preguntaId',
        'respuesta',
    ];

    public function precalificacion(): BelongsTo
    {
        return $this->belongsTo(Precalificacion::class, 'precalificacionId');
    }

    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(PreguntaPrecalificacion::class, 'preguntaId');
    }
}
