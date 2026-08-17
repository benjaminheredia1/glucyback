<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $fillable = [
        'usuarioId',
        'tipo',
        'titulo',
        'cuerpo',
        'data',
        'enviadaEn',
        'leidaEn',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'enviadaEn' => 'datetime',
            'leidaEn' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuarioId');
    }
}
