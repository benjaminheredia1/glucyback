<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Suscripcion extends Model
{
    use SoftDeletes;

    protected $table = 'suscripciones';

    protected $fillable = [
        'pacienteId',
        'planId',
        'licenciaId',
        'estado',
        'inicio',
        'fin',
        'proximoCobro',
        'consultasUsadas',
    ];

    protected function casts(): array
    {
        return [
            'inicio' => 'date',
            'fin' => 'date',
            'proximoCobro' => 'date',
            'consultasUsadas' => 'integer',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'pacienteId');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'planId');
    }

    public function licencia(): BelongsTo
    {
        return $this->belongsTo(Licencia::class, 'licenciaId');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'suscripcionId');
    }

    public function consultasDisponibles(): int
    {
        return max(0, ($this->plan?->consultasIncluidas ?? 0) - $this->consultasUsadas);
    }
}
