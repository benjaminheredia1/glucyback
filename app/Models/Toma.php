<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Toma extends Model
{
    protected $table = 'tomas';

    protected $fillable = [
        'pacienteMedicamentoId',
        'programadaEn',
        'tomadaEn',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'programadaEn' => 'datetime',
            'tomadaEn' => 'datetime',
        ];
    }

    public function pacienteMedicamento(): BelongsTo
    {
        return $this->belongsTo(PacienteMedicamento::class, 'pacienteMedicamentoId');
    }
}
