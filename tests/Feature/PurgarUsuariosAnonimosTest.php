<?php

namespace Tests\Feature;

use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurgarUsuariosAnonimosTest extends TestCase
{
    use RefreshDatabase;

    public function test_purga_al_anonimo_viejo_sin_actividad(): void
    {
        $viejo = $this->anonimoDeHace(40);

        $this->artisan('usuarios:purgar-anonimos')->assertSuccessful();

        $this->assertSoftDeleted('users', ['id' => $viejo->id]);
        $this->assertSoftDeleted('pacientes', ['usuarioId' => $viejo->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $viejo->id]);
        $this->assertDatabaseHas('audit_logs', [
            'usuarioId' => null,
            'entidad' => 'User',
            'entidadId' => $viejo->id,
            'accion' => 'purgar-anonimo',
        ]);
    }

    public function test_conserva_al_anonimo_viejo_con_token_usado_hace_poco(): void
    {
        $activo = $this->anonimoDeHace(40, tokenUsadoHace: 2);

        $this->artisan('usuarios:purgar-anonimos')->assertSuccessful();

        $this->assertNotSoftDeleted('users', ['id' => $activo->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $activo->id]);
    }

    public function test_conserva_al_anonimo_reciente(): void
    {
        $reciente = $this->anonimoDeHace(5);

        $this->artisan('usuarios:purgar-anonimos')->assertSuccessful();

        $this->assertNotSoftDeleted('users', ['id' => $reciente->id]);
    }

    public function test_nunca_toca_usuarios_con_correo(): void
    {
        $registrado = User::factory()->create(['rol' => User::ROL_PACIENTE]);
        User::whereKey($registrado->id)->update([
            'created_at' => now()->subDays(400),
            'updated_at' => now()->subDays(400),
        ]);

        $this->artisan('usuarios:purgar-anonimos')->assertSuccessful();

        $this->assertNotSoftDeleted('users', ['id' => $registrado->id]);
    }

    public function test_dry_run_lista_sin_borrar(): void
    {
        $viejo = $this->anonimoDeHace(40);

        $this->artisan('usuarios:purgar-anonimos --dry-run')
            ->expectsOutputToContain("#{$viejo->id}")
            ->assertSuccessful();

        $this->assertNotSoftDeleted('users', ['id' => $viejo->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $viejo->id]);
    }

    public function test_dias_pisa_la_configuracion(): void
    {
        $reciente = $this->anonimoDeHace(5);

        $this->artisan('usuarios:purgar-anonimos --dias=3')->assertSuccessful();

        $this->assertSoftDeleted('users', ['id' => $reciente->id]);
    }

    /**
     * Anonimo creado hace $dias dias con un token; si $tokenUsadoHace no es
     * null, ese token registra uso hace esa cantidad de dias.
     */
    private function anonimoDeHace(int $dias, ?int $tokenUsadoHace = null): User
    {
        $usuario = User::create([
            'name' => User::NOMBRE_TEMPORAL,
            'email' => null,
            'password' => null,
            'rol' => User::ROL_PACIENTE,
        ]);
        Paciente::create(['usuarioId' => $usuario->id]);

        $token = $usuario->createToken('api')->accessToken;

        if ($tokenUsadoHace !== null) {
            $token->forceFill(['last_used_at' => now()->subDays($tokenUsadoHace)])->save();
        }

        // Query builder a proposito: save() pisaria updated_at con "ahora".
        User::whereKey($usuario->id)->update([
            'created_at' => now()->subDays($dias),
            'updated_at' => now()->subDays($dias),
        ]);

        return $usuario->fresh();
    }
}
