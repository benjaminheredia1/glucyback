<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuarioTemporalTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_usuario_puede_nacer_sin_correo_y_es_temporal(): void
    {
        $usuario = User::create([
            'name' => User::NOMBRE_TEMPORAL,
            'email' => null,
            'password' => null,
            'rol' => User::ROL_PACIENTE,
        ]);

        $fresco = $usuario->fresh();

        $this->assertNull($fresco->email);
        $this->assertTrue($fresco->esTemporal());
        // La app decide con esto si pedir registro: tiene que viajar en el JSON.
        $this->assertTrue($fresco->toArray()['esTemporal']);
    }

    public function test_varios_anonimos_conviven_y_el_correo_sigue_siendo_unico(): void
    {
        User::create(['name' => User::NOMBRE_TEMPORAL, 'email' => null, 'password' => null, 'rol' => User::ROL_PACIENTE]);
        User::create(['name' => User::NOMBRE_TEMPORAL, 'email' => null, 'password' => null, 'rol' => User::ROL_PACIENTE]);

        $this->assertSame(2, User::whereNull('email')->count());

        // El indice unico tiene que sobrevivir al change() de la migracion.
        User::factory()->create(['email' => 'ana@ejemplo.com']);

        $this->expectException(QueryException::class);
        User::factory()->create(['email' => 'ana@ejemplo.com']);
    }

    public function test_un_usuario_con_correo_no_es_temporal(): void
    {
        $usuario = User::factory()->create();

        $this->assertFalse($usuario->esTemporal());
        $this->assertFalse($usuario->toArray()['esTemporal']);
    }
}
