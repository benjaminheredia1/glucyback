<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Alerta extends Model
{
    use SoftDeletes;

    protected $table = 'alertas';

    protected $fillable = [
        'pacienteId',
        'medicionId',
        'reglaId',
        'casoId',
        'tipo',
        'severidad',
        'mensaje',
        'estado',
        'atendidaPor',
        'atendidaEn',
    ];

    protected function casts(): array
    {
        return [
            'atendidaEn' => 'datetime',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'pacienteId');
    }

    public function medicion(): BelongsTo
    {
        return $this->belongsTo(Medicion::class, 'medicionId');
    }

    public function regla(): BelongsTo
    {
        return $this->belongsTo(ReglaAlerta::class, 'reglaId');
    }

    public function caso(): BelongsTo
    {
        return $this->belongsTo(Caso::class, 'casoId');
    }

    public function atendidaPorDoctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'atendidaPor');
    }
}
