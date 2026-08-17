<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArticuloAyuda extends Model
{
    use SoftDeletes;

    protected $table = 'articulos_ayuda';

    protected $fillable = [
        'categoria',
        'titulo',
        'cuerpo',
        'orden',
        'publicado',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'publicado' => 'boolean',
        ];
    }
}
