<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Elegibilidad extends Model
{
    use SoftDeletes;

    protected $table = 'elegibilidades';

    protected $fillable = [
        'pacienteId',
        'nroInforme',
        'semaforo',
        'resumen',
        'criterios',
        'generadaEn',
        'vigenciaHasta',
    ];

    protected function casts(): array
    {
        return [
            'criterios' => 'array',
            'generadaEn' => 'datetime',
            'vigenciaHasta' => 'date',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'pacienteId');
    }

    public function estaVigente(): bool
    {
        return $this->vigenciaHasta === null || $this->vigenciaHasta->isFuture();
    }
}
