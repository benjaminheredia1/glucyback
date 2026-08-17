<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mensaje extends Model
{
    use SoftDeletes;

    protected $table = 'mensajes';

    protected $fillable = [
        'chatId',
        'autorId',
        'archivoId',
        'pacienteId',
        'texto',
        'esIA',
        'leidoEn',
    ];

    protected function casts(): array
    {
        return [
            'esIA' => 'boolean',
            'leidoEn' => 'datetime',
        ];
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class, 'chatId');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autorId');
    }

    public function archivo(): BelongsTo
    {
        return $this->belongsTo(Archivo::class, 'archivoId');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'pacienteId');
    }
}
