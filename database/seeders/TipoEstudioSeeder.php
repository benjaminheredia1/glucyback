<?php

namespace Database\Seeders;

use App\Models\TipoEstudio;
use Illuminate\Database\Seeder;

class TipoEstudioSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            ['Glucemia en ayunas', 'mg/dL', 70, 100, true],
            ['Hemoglobina glicosilada (HbA1c)', '%', 4.0, 5.7, true],
            ['Creatinina', 'mg/dL', 0.6, 1.3, true],
            ['Péptido C', 'ng/mL', 0.5, 2.0, true],
            ['Perfil lipídico', 'mg/dL', null, 200, true],
            ['Transaminasas (ALT/AST)', 'U/L', 0, 40, true],
        ];

        foreach ($tipos as $orden => [$nombre, $unidad, $min, $max, $obligatorio]) {
            TipoEstudio::updateOrCreate(
                ['nombre' => $nombre],
                [
                    'unidad' => $unidad,
                    'rangoMin' => $min,
                    'rangoMax' => $max,
                    'esObligatorio' => $obligatorio,
                    'orden' => $orden + 1,
                ]
            );
        }
    }
}
