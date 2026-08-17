<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EstudioMedico extends Model
{
    use SoftDeletes;

    protected $table = 'estudios_medicos';

    protected $fillable = [
        'tipoEstudioId',
        'pacienteId',
        'archivoId',
        'descripcion',
        'fecha',
        'valor',
        'unidad',
        'estado',
        'motivoRechazo',
        'validadoPor',
        'validadoEn',
        'intento',
        'origen',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'validadoEn' => 'datetime',
            'valor' => 'float',
            'intento' => 'integer',
        ];
    }

    public function tipoEstudio(): BelongsTo
    {
        return $this->belongsTo(TipoEstudio::class, 'tipoEstudioId');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'pacienteId');
    }

    public function archivo(): BelongsTo
    {
        return $this->belongsTo(Archivo::class, 'archivoId');
    }

    public function validador(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'validadoPor');
    }
}
