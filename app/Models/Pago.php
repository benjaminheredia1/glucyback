<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pago extends Model
{
    use SoftDeletes;

    protected $table = 'pagos';

    protected $fillable = [
        'suscripcionId',
        'monto',
        'moneda',
        'metodo',
        'estado',
        'referencia',
        'pagadoEn',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'pagadoEn' => 'datetime',
        ];
    }

    public function suscripcion(): BelongsTo
    {
        return $this->belongsTo(Suscripcion::class, 'suscripcionId');
    }

    public function comprobante(): HasOne
    {
        return $this->hasOne(Comprobante::class, 'pagoId');
    }
}
