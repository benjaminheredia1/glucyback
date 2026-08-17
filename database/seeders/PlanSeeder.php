<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $planes = [
            ['Mensual', 'paciente', 25.00, 'mensual', 2, 14, 'Seguimiento con IA y validación médica cada 15 días'],
            ['Anual', 'paciente', 250.00, 'anual', 2, 14, 'Ahorras 2 meses · prioridad en la validación médica'],
            ['Básico', 'clinica', 199.00, 'mensual', 0, 0, 'Hasta 60 licencias de paciente'],
            ['Institucional', 'clinica', 499.00, 'mensual', 0, 0, 'Licencias ilimitadas y SLA prioritario'],
        ];

        foreach ($planes as [$nombre, $ambito, $precio, $periodicidad, $consultas, $prueba, $descripcion]) {
            Plan::updateOrCreate(
                ['nombre' => $nombre, 'ambito' => $ambito],
                [
                    'descripcion' => $descripcion,
                    'precio' => $precio,
                    'moneda' => 'USD',
                    'periodicidad' => $periodicidad,
                    'consultasIncluidas' => $consultas,
                    'diasPrueba' => $prueba,
                    'activo' => true,
                ]
            );
        }
    }
}
