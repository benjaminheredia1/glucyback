<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Caso extends Model
{
    use SoftDeletes;

    protected $table = 'casos';

    protected $fillable = [
        'pacienteId',
        'doctorId',
        'cicloId',
        'tipo',
        'urgencia',
        'estado',
        'titulo',
        'nota',
        'abiertoEn',
        'asignadoEn',
        'cerradoEn',
    ];

    protected function casts(): array
    {
        return [
            'abiertoEn' => 'datetime',
            'asignadoEn' => 'datetime',
            'cerradoEn' => 'datetime',
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

    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(Ciclo::class, 'cicloId');
    }

    public function diagnosticos(): HasMany
    {
        return $this->hasMany(Diagnostico::class, 'casoId');
    }

    public function tratamientos(): HasMany
    {
        return $this->hasMany(Tratamiento::class, 'casoId');
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(Alerta::class, 'casoId');
    }

    /**
     * SLA de resolucion en minutos. Null mientras el caso siga abierto.
     */
    public function slaMinutos(): ?int
    {
        if ($this->cerradoEn === null) {
            return null;
        }

        return (int) $this->abiertoEn->diffInMinutes($this->cerradoEn);
    }
}
