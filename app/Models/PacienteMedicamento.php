<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PacienteMedicamento extends Model
{
    use SoftDeletes;

    protected $table = 'paciente_medicamentos';

    protected $fillable = [
        'pacienteId',
        'tratamientoId',
        'medicamentoId',
        'dosis',
        'frecuencia',
        'indicaciones',
        'fechaInicio',
        'fechaFin',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'fechaInicio' => 'date',
            'fechaFin' => 'date',
            'activo' => 'boolean',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'pacienteId');
    }

    public function tratamiento(): BelongsTo
    {
        return $this->belongsTo(Tratamiento::class, 'tratamientoId');
    }

    public function medicamento(): BelongsTo
    {
        return $this->belongsTo(Medicamento::class, 'medicamentoId');
    }

    public function tomas(): HasMany
    {
        return $this->hasMany(Toma::class, 'pacienteMedicamentoId');
    }
}
