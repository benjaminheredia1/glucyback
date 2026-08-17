<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tratamiento extends Model
{
    use SoftDeletes;

    protected $table = 'tratamientos';

    protected $fillable = [
        'pacienteId',
        'doctorId',
        'casoId',
        'cicloId',
        'descripcion',
        'tratamientoAI',
        'tratamientoDoctor',
        'notaDoctor',
        'estado',
        'firmadoEn',
        'enviadoEn',
        'version',
        'reemplazaA',
    ];

    protected function casts(): array
    {
        return [
            'firmadoEn' => 'datetime',
            'enviadoEn' => 'datetime',
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

    public function anterior(): BelongsTo
    {
        return $this->belongsTo(Tratamiento::class, 'reemplazaA');
    }

    public function reemplazo(): HasOne
    {
        return $this->hasOne(Tratamiento::class, 'reemplazaA');
    }

    public function medicamentos(): HasMany
    {
        return $this->hasMany(PacienteMedicamento::class, 'tratamientoId');
    }

    public function estaFirmado(): bool
    {
        return in_array($this->estado, ['firmado', 'enviado'], true);
    }
}
