<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Medicion extends Model
{
    use SoftDeletes;

    protected $table = 'mediciones';

    protected $fillable = [
        'pacienteId',
        'cicloId',
        'valor',
        'unidad',
        'momento',
        'fuente',
        'medidoEn',
        'nota',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'float',
            'medidoEn' => 'datetime',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'pacienteId');
    }

    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(Ciclo::class, 'cicloId');
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(Alerta::class, 'medicionId');
    }
}
