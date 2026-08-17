<?php

namespace Database\Seeders;

use App\Models\Medicamento;
use Illuminate\Database\Seeder;

class MedicamentoSeeder extends Seeder
{
    public function run(): void
    {
        $medicamentos = [
            ['Metformina', '850 mg', 'comprimido', 'oral'],
            ['Metformina', '500 mg', 'comprimido', 'oral'],
            ['Insulina NPH', '100 UI/mL', 'vial', 'subcutánea'],
            ['Insulina glargina', '100 UI/mL', 'lapicera', 'subcutánea'],
            ['Glibenclamida', '5 mg', 'comprimido', 'oral'],
            ['Empagliflozina', '10 mg', 'comprimido', 'oral'],
            ['Sitagliptina', '100 mg', 'comprimido', 'oral'],
        ];

        foreach ($medicamentos as [$nombre, $concentracion, $presentacion, $via]) {
            Medicamento::updateOrCreate(
                ['nombre' => $nombre, 'concentracion' => $concentracion],
                ['presentacion' => $presentacion, 'viaAdministracion' => $via, 'activo' => true]
            );
        }
    }
}
