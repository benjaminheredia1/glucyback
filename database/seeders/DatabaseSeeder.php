<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            PreguntaPrecalificacionSeeder::class,
            TipoEstudioSeeder::class,
            ReglaAlertaSeeder::class,
            MedicamentoSeeder::class,
        ]);

        User::updateOrCreate(
            ['email' => 'admin@glucy.test'],
            [
                'name' => 'Admin',
                'apellidoPaterno' => 'Glucy',
                'password' => Hash::make('password'),
                'rol' => User::ROL_ADMIN,
                'email_verified_at' => now(),
            ]
        );
    }
}
