<?php

namespace Database\Seeders;

use App\Models\ReglaAlerta;
use Illuminate\Database\Seeder;

/**
 * Umbrales globales. Una clinica puede definir los suyos y esos ganan.
 */
class ReglaAlertaSeeder extends Seeder
{
    public function run(): void
    {
        $reglas = [
            ['cualquiera', null, 250, 'critica', 'Valor muy por encima de tu rango objetivo. Volvé a medir en 2 horas.'],
            ['cualquiera', 54, null, 'critica', 'Hipoglucemia severa. Tomá azúcar de acción rápida y contactá a tu médico.'],
            ['ayunas', null, 130, 'alta', 'Glucemia en ayunas por encima de la meta.'],
            ['postprandial', null, 180, 'alta', 'Glucemia post comida por encima de la meta.'],
            ['cualquiera', 70, null, 'media', 'Glucemia baja. Revisá tu último registro de comida.'],
        ];

        foreach ($reglas as [$momento, $min, $max, $severidad, $mensaje]) {
            ReglaAlerta::updateOrCreate(
                ['clinicaId' => null, 'momento' => $momento, 'minimo' => $min, 'maximo' => $max],
                ['severidad' => $severidad, 'mensaje' => $mensaje, 'activa' => true]
            );
        }
    }
}
