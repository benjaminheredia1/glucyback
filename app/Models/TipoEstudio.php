<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoEstudio extends Model
{
    use SoftDeletes;

    protected $table = 'tipo_estudios';

    protected $fillable = [
        'nombre',
        'descripcion',
        'unidad',
        'rangoMin',
        'rangoMax',
        'esObligatorio',
        'orden',
        'codigoLoinc',
    ];

    protected function casts(): array
    {
        return [
            'rangoMin' => 'float',
            'rangoMax' => 'float',
            'esObligatorio' => 'boolean',
            'orden' => 'integer',
        ];
    }

    public function estudiosMedicos(): HasMany
    {
        return $this->hasMany(EstudioMedico::class, 'tipoEstudioId');
    }

    /**
     * Un valor solo se puede evaluar si el catalogo define rangos de referencia.
     */
    public function evalua(?float $valor): ?bool
    {
        if ($valor === null || ($this->rangoMin === null && $this->rangoMax === null)) {
            return null;
        }

        if ($this->rangoMin !== null && $valor < $this->rangoMin) {
            return false;
        }

        if ($this->rangoMax !== null && $valor > $this->rangoMax) {
            return false;
        }

        return true;
    }
}
