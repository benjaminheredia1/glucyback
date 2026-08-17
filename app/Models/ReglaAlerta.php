<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReglaAlerta extends Model
{
    protected $table = 'reglas_alerta';

    protected $fillable = [
        'clinicaId',
        'momento',
        'minimo',
        'maximo',
        'severidad',
        'mensaje',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'minimo' => 'float',
            'maximo' => 'float',
            'activa' => 'boolean',
        ];
    }

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class, 'clinicaId');
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(Alerta::class, 'reglaId');
    }

    public function aplicaA(Medicion $medicion): bool
    {
        if (! $this->activa) {
            return false;
        }

        if ($this->momento !== 'cualquiera' && $this->momento !== $medicion->momento) {
            return false;
        }

        return ($this->minimo !== null && $medicion->valor < $this->minimo)
            || ($this->maximo !== null && $medicion->valor > $this->maximo);
    }
}
