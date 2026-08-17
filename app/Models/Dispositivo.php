<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispositivo extends Model
{
    protected $table = 'dispositivos';

    protected $fillable = [
        'usuarioId',
        'pushToken',
        'plataforma',
        'modelo',
        'ultimoUsoEn',
    ];

    protected function casts(): array
    {
        return [
            'ultimoUsoEn' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuarioId');
    }
}
