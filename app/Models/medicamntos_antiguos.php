<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Medicacion actual que el paciente declara en su perfil (nombre y cantidad
 * en texto libre). La tabla no lleva timestamps.
 */
class medicamntos_antiguos extends Model
{
    protected $table = 'paciente_medicamentos_antiguos';

    public $timestamps = false;

    protected $fillable = ['nombre', 'cantidad', 'paciente_id'];

    /** String "Metformina (850 mg), Enalapril" para los prompts de la IA. */
    public static function obtenerMedicamentosAntiguos($pacienteId): string
    {
        return self::where('paciente_id', $pacienteId)
            ->get(['nombre', 'cantidad'])
            ->map(fn (self $m) => $m->cantidad === null || $m->cantidad === ''
                ? (string) $m->nombre
                : "{$m->nombre} ({$m->cantidad})")
            ->implode(', ');
    }
}
