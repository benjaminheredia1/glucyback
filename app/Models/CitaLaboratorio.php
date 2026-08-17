<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CitaLaboratorio extends Model
{
    use SoftDeletes;

    protected $table = 'citas_laboratorio';

    protected $fillable = [
        'pacienteId',
        'laboratorioId',
        'fecha',
        'franja',
        'direccion',
        'referencia',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'pacienteId');
    }

    public function laboratorio(): BelongsTo
    {
        return $this->belongsTo(Laboratorio::class, 'laboratorioId');
    }
}
