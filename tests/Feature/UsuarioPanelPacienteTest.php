<?php

namespace Tests\Feature;

use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Toda cuenta con rol paciente necesita su fila en pacientes: sin ella
 * Alcance::pacienteId() devuelve null y la API responde 422 "El usuario no
 * tiene perfil de paciente". El alta y el cambio de rol desde el panel
 * (POST/PATCH /usuarios) tambien tienen que garantizarla.
 */
class UsuarioPanelPacienteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
    }

    public function test_crear_un_usuario_paciente_desde_el_panel_crea_su_fila_de_pacientes(): void
    {
        $respuesta = $this->actingAs($this->admin)->postJson('/api/usuarios', [
            'name' => 'Paciente Panel',
            'email' => 'panel@glucy.test',
            'password' => 'secreta12345',
            'password_confirmation' => 'secreta12345',
            'rol' => User::ROL_PACIENTE,
        ]);

        $respuesta->assertCreated();

        $usuario = User::where('email', 'panel@glucy.test')->sole();
        $this->assertDatabaseHas('pacientes', ['usuarioId' => $usuario->id, 'deleted_at' => null]);
    }

    public function test_cambiar_el_rol_a_paciente_tambien_crea_la_fila(): void
    {
        $doctor = User::factory()->create(['rol' => User::ROL_DOCTOR]);
        $this->assertDatabaseMissing('pacientes', ['usuarioId' => $doctor->id]);

        $this->actingAs($this->admin)
            ->patchJson("/api/usuarios/{$doctor->id}", ['rol' => User::ROL_PACIENTE])
            ->assertOk();

        $this->assertDatabaseHas('pacientes', ['usuarioId' => $doctor->id, 'deleted_at' => null]);
    }

    public function test_crear_un_doctor_no_crea_fila_de_pacientes(): void
    {
        $this->actingAs($this->admin)->postJson('/api/usuarios', [
            'name' => 'Doctor Panel',
            'email' => 'doc-panel@glucy.test',
            'password' => 'secreta12345',
            'password_confirmation' => 'secreta12345',
            'rol' => User::ROL_DOCTOR,
        ])->assertCreated();

        $usuario = User::where('email', 'doc-panel@glucy.test')->sole();
        $this->assertDatabaseMissing('pacientes', ['usuarioId' => $usuario->id]);
    }
}
