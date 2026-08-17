<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ciclo extends Model
{
    use SoftDeletes;

    protected $table = 'ciclos';

    protected $fillable = [
        'pacienteId',
        'numero',
        'inicio',
        'fin',
        'medicionesRequeridas',
        'medicionesRegistradas',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'inicio' => 'date',
            'fin' => 'date',
            'numero' => 'integer',
            'medicionesRequeridas' => 'integer',
            'medicionesRegistradas' => 'integer',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'pacienteId');
    }

    public function mediciones(): HasMany
    {
        return $this->hasMany(Medicion::class, 'cicloId');
    }

    public function casos(): HasMany
    {
        return $this->hasMany(Caso::class, 'cicloId');
    }

    public function tratamientos(): HasMany
    {
        return $this->hasMany(Tratamiento::class, 'cicloId');
    }

    public function diagnosticos(): HasMany
    {
        return $this->hasMany(Diagnostico::class, 'cicloId');
    }

    public function estaCompleto(): bool
    {
        return $this->medicionesRegistradas >= $this->medicionesRequeridas;
    }
}
