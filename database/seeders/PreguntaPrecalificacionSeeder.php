<?php

namespace Database\Seeders;

use App\Models\PreguntaPrecalificacion;
use Illuminate\Database\Seeder;

/**
 * Filtro clinico version 1. Una alarma detiene el proceso antes de pedir estudios.
 */
class PreguntaPrecalificacionSeeder extends Seeder
{
    public function run(): void
    {
        $preguntas = [
            ['q1', '¿Tienes 18 años o más?', 'no', false, 'ser menor de 18 años'],
            ['q2', '¿Estás embarazada o en lactancia?', 'si', false, 'embarazo o lactancia'],
            ['q3', '¿Diagnóstico previo de diabetes tipo 1 o uso de bomba de insulina?', 'si', false, 'diabetes tipo 1 o bomba de insulina'],
            ['q4', '¿Tienes ahora vómitos persistentes, dolor abdominal intenso, respiración rápida o confusión?', 'si', true, 'síntomas de descompensación aguda'],
            ['q5', '¿Diálisis o enfermedad renal avanzada?', 'si', false, 'diálisis o enfermedad renal avanzada'],
            ['q6', '¿Cirrosis o enfermedad hepática avanzada?', 'si', false, 'cirrosis o enfermedad hepática avanzada'],
            ['q7', '¿Cáncer en tratamiento activo o trasplante de órgano?', 'si', false, 'cáncer en tratamiento activo o trasplante'],
            ['q8', '¿Insuficiencia cardíaca severa o infarto en los últimos 6 meses?', 'si', false, 'insuficiencia cardíaca severa o infarto reciente'],
            ['q9', '¿Tomas corticoides de forma crónica?', 'si', false, 'uso crónico de corticoides'],
        ];

        foreach ($preguntas as $orden => [$codigo, $texto, $alarma, $urgente, $motivo]) {
            PreguntaPrecalificacion::updateOrCreate(
                ['codigo' => $codigo, 'version' => 1],
                [
                    'texto' => $texto,
                    'respuestaAlarma' => $alarma,
                    'esUrgente' => $urgente,
                    'motivo' => $motivo,
                    'orden' => $orden + 1,
                    'activa' => true,
                ]
            );
        }
    }
}
