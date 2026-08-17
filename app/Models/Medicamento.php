<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Medicamento extends Model
{
    use SoftDeletes;

    protected $table = 'medicamentos';

    protected $fillable = [
        'nombre',
        'concentracion',
        'presentacion',
        'viaAdministracion',
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function prescripciones(): HasMany
    {
        return $this->hasMany(PacienteMedicamento::class, 'medicamentoId');
    }
}
