<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class UsuarioAuth0Test extends TestCase
{
    use RefreshDatabase;

    public function test_un_usuario_puede_existir_sin_password(): void
    {
        $usuario = User::create([
            'name' => 'Maria',
            'email' => 'maria@ejemplo.com',
            'rol' => User::ROL_PACIENTE,
            'auth0Sub' => 'auth0|abc123',
        ]);

        $this->assertNull($usuario->fresh()->password);
        $this->assertSame('auth0|abc123', $usuario->fresh()->auth0Sub);
    }

    public function test_el_sub_de_auth0_es_unico(): void
    {
        User::factory()->conAuth0('auth0|abc123')->create();

        $this->expectException(QueryException::class);

        User::factory()->conAuth0('auth0|abc123')->create();
    }

    public function test_el_sub_de_auth0_admite_nulo_en_varios_usuarios(): void
    {
        User::factory()->count(2)->create(['auth0Sub' => null]);

        $this->assertSame(2, User::whereNull('auth0Sub')->count());
    }
}
