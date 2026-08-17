<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chat extends Model
{
    use SoftDeletes;

    protected $table = 'chats';

    protected $fillable = [
        'nombre',
        'pacienteId',
        'tipo',
        'estado',
        'ultimoMensajeEn',
    ];

    protected function casts(): array
    {
        return [
            'ultimoMensajeEn' => 'datetime',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'pacienteId');
    }

    public function mensajes(): HasMany
    {
        return $this->hasMany(Mensaje::class, 'chatId');
    }
}
