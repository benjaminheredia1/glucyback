<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenciaUsuario extends Model
{
    protected $table = 'licencia_usuarios';

    protected $fillable = [
        'usuarioId',
        'licenciaId',
        'estado',
        'asignadoEn',
        'revocadoEn',
    ];

    protected function casts(): array
    {
        return [
            'asignadoEn' => 'datetime',
            'revocadoEn' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuarioId');
    }

    public function licencia(): BelongsTo
    {
        return $this->belongsTo(Licencia::class, 'licenciaId');
    }
}
