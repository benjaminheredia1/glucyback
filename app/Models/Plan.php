<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use SoftDeletes;

    protected $table = 'planes';

    protected $fillable = [
        'nombre',
        'descripcion',
        'ambito',
        'precio',
        'moneda',
        'periodicidad',
        'consultasIncluidas',
        'diasPrueba',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'consultasIncluidas' => 'integer',
            'diasPrueba' => 'integer',
            'activo' => 'boolean',
        ];
    }

    public function suscripciones(): HasMany
    {
        return $this->hasMany(Suscripcion::class, 'planId');
    }

    public function licencias(): HasMany
    {
        return $this->hasMany(Licencia::class, 'planId');
    }

    public function clinicas(): HasMany
    {
        return $this->hasMany(Clinica::class, 'planId');
    }
}
