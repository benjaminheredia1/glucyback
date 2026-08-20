<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class medicamntos_antiguos extends Model
{
    protected $table = 'paciente_medicamentos_antiguos';

    public static function obtenerMedicamentosAntiguos($pacienteId): string
    {
        return self::where('paciente_id', $pacienteId)
            ->pluck('nombre')
            ->implode(', ');
    }
}
