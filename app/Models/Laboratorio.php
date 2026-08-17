<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Laboratorio extends Model
{
    use SoftDeletes;

    protected $table = 'laboratorios';

    protected $fillable = [
        'nombre',
        'telefono',
        'direccion',
        'cobertura',
        'aDomicilio',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'aDomicilio' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function citas(): HasMany
    {
        return $this->hasMany(CitaLaboratorio::class, 'laboratorioId');
    }
}
