<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Licencia extends Model
{
    use SoftDeletes;

    protected $table = 'licencias';

    protected $fillable = [
        'codigo',
        'nombre',
        'estado',
        'clinicaId',
        'planId',
        'cantidad',
        'usadas',
        'fecha_expiracion',
        'descuento',
    ];

    protected function casts(): array
    {
        return [
            'fecha_expiracion' => 'date',
            'cantidad' => 'integer',
            'usadas' => 'integer',
            'descuento' => 'float',
        ];
    }

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class, 'clinicaId');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'planId');
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(LicenciaUsuario::class, 'licenciaId');
    }

    public function suscripciones(): HasMany
    {
        return $this->hasMany(Suscripcion::class, 'licenciaId');
    }

    public function cuposDisponibles(): int
    {
        return max(0, $this->cantidad - $this->usadas);
    }

    public function estaVencida(): bool
    {
        return $this->fecha_expiracion !== null && $this->fecha_expiracion->isPast();
    }
}
