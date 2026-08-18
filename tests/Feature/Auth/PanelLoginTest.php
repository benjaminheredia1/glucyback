<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelLoginTest extends TestCase
{
    use RefreshDatabase;

    private function crear(string $rol, ?string $password = 'secreto-largo-123'): User
    {
        return User::create([
            'name' => 'Persona',
            'email' => "$rol@ejemplo.com",
            'password' => $password,
            'rol' => $rol,
        ]);
    }

    public function test_admin_entra_con_correo_y_contrasena(): void
    {
        $admin = $this->crear(User::ROL_ADMIN);

        $respuesta = $this->postJson('/api/auth/panel', [
            'email' => 'Admin@Ejemplo.com',
            'password' => 'secreto-largo-123',
            'dispositivo' => 'glucyfront',
        ]);

        $respuesta->assertOk()
            ->assertJsonStructure(['token', 'usuario' => ['id', 'email', 'rol']])
            ->assertJsonPath('usuario.id', $admin->id)
            ->assertJsonPath('usuario.rol', User::ROL_ADMIN);

        // El token sirve para el resto de la API.
        $this->withHeader('Authorization', 'Bearer '.$respuesta->json('token'))
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id', $admin->id);
    }

    public function test_doctor_tambien_entra(): void
    {
        $this->crear(User::ROL_DOCTOR);

        $this->postJson('/api/auth/panel', ['email' => 'doctor@ejemplo.com', 'password' => 'secreto-largo-123'])
            ->assertOk()
            ->assertJsonPath('usuario.rol', User::ROL_DOCTOR);
    }

    public function test_paciente_no_entra_al_panel(): void
    {
        $this->crear(User::ROL_PACIENTE);

        $this->postJson('/api/auth/panel', ['email' => 'paciente@ejemplo.com', 'password' => 'secreto-largo-123'])
            ->assertForbidden();
    }

    public function test_contrasena_incorrecta_o_cuenta_inexistente_dan_401(): void
    {
        $this->crear(User::ROL_ADMIN);

        $this->postJson('/api/auth/panel', ['email' => 'admin@ejemplo.com', 'password' => 'otra'])
            ->assertUnauthorized();

        $this->postJson('/api/auth/panel', ['email' => 'nadie@ejemplo.com', 'password' => 'otra'])
            ->assertUnauthorized();
    }

    public function test_cuenta_solo_auth0_sin_contrasena_da_401(): void
    {
        $this->crear(User::ROL_ADMIN, null);

        $this->postJson('/api/auth/panel', ['email' => 'admin@ejemplo.com', 'password' => 'lo-que-sea'])
            ->assertUnauthorized();
    }

    public function test_reentrar_desde_el_mismo_dispositivo_no_acumula_tokens(): void
    {
        $admin = $this->crear(User::ROL_ADMIN);
        $credenciales = ['email' => 'admin@ejemplo.com', 'password' => 'secreto-largo-123', 'dispositivo' => 'glucyfront'];

        $this->postJson('/api/auth/panel', $credenciales)->assertOk();
        $this->postJson('/api/auth/panel', $credenciales)->assertOk();

        $this->assertSame(1, $admin->tokens()->count());
    }
}
