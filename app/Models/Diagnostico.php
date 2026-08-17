<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Diagnostico extends Model
{
    use SoftDeletes;

    protected $table = 'diagnosticos';

    protected $fillable = [
        'pacienteId',
        'doctorId',
        'casoId',
        'cicloId',
        'descripcion',
        'diagnosticoAI',
        'diagnosticoDoctor',
        'estado',
        'firmadoEn',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'firmadoEn' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'pacienteId');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctorId');
    }

    public function caso(): BelongsTo
    {
        return $this->belongsTo(Caso::class, 'casoId');
    }

    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(Ciclo::class, 'cicloId');
    }

    public function estaFirmado(): bool
    {
        return $this->estado === 'firmado';
    }
}
