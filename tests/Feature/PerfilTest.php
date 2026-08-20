<?php

namespace Tests\Feature;

use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerfilTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Paciente}
     */
    private function crearPaciente(): array
    {
        $usuario = User::create([
            'name' => 'Paciente',
            'email' => 'paciente@glucy.test',
            'password' => 'secret1234',
            'rol' => User::ROL_PACIENTE,
        ]);

        $paciente = Paciente::create(['usuarioId' => $usuario->id, 'tipoDiabetes' => 'DM2']);

        return [$usuario, $paciente];
    }

    public function test_paciente_actualiza_su_perfil_completo(): void
    {
        [$usuario, $paciente] = $this->crearPaciente();

        $respuesta = $this->actingAs($usuario)->patchJson('/api/perfil', [
            'name' => 'Benjamin',
            'apellidoPaterno' => 'Heredia',
            'telefono' => '+591 700 00000',
            'fechaNacimiento' => '1990-05-01',
            'sexo' => 'masculino',
            'pesoKg' => 74.5,
            'tallaCm' => 172,
        ]);

        $respuesta->assertOk()
            ->assertJsonPath('name', 'Benjamin')
            ->assertJsonPath('paciente.sexo', 'masculino');

        $this->assertSame('Heredia', $usuario->fresh()->apellidoPaterno);
        $this->assertSame(172, $paciente->fresh()->tallaCm);
    }

    public function test_no_puede_cambiar_email_ni_rol(): void
    {
        [$usuario] = $this->crearPaciente();

        $this->actingAs($usuario)->patchJson('/api/perfil', [
            'email' => 'otro@glucy.test',
            'rol' => 'admin',
            'name' => 'Solo Nombre',
        ])->assertOk();

        $fresco = $usuario->fresh();
        $this->assertSame('paciente@glucy.test', $fresco->email);
        $this->assertSame(User::ROL_PACIENTE, $fresco->rol);
        $this->assertSame('Solo Nombre', $fresco->name);
    }

    public function test_guarda_y_reemplaza_la_medicacion_actual(): void
    {
        [$usuario, $paciente] = $this->crearPaciente();

        $respuesta = $this->actingAs($usuario)->patchJson('/api/perfil', [
            'medicacionActual' => [
                ['nombre' => 'Metformina', 'cantidad' => '850 mg'],
                ['nombre' => 'Enalapril'],
            ],
        ]);

        $respuesta->assertOk()
            ->assertJsonCount(2, 'paciente.medicacion_actual')
            ->assertJsonPath('paciente.medicacion_actual.0.nombre', 'Metformina')
            ->assertJsonPath('paciente.medicacion_actual.0.cantidad', '850 mg')
            ->assertJsonPath('paciente.medicacion_actual.1.cantidad', null);

        // La lista se reemplaza completa, no se acumula.
        $this->actingAs($usuario)->patchJson('/api/perfil', [
            'medicacionActual' => [['nombre' => 'Insulina glargina', 'cantidad' => '10 UI']],
        ])->assertOk()->assertJsonCount(1, 'paciente.medicacion_actual');

        $this->assertSame(
            ['Insulina glargina'],
            $paciente->medicacionActual()->pluck('nombre')->all(),
        );

        // Mandar [] la vacia; omitir el campo la deja intacta.
        $this->actingAs($usuario)->patchJson('/api/perfil', ['medicacionActual' => []])
            ->assertOk()->assertJsonCount(0, 'paciente.medicacion_actual');

        $this->actingAs($usuario)->patchJson('/api/perfil', ['name' => 'Sin tocar medicacion'])
            ->assertOk();
        $this->assertSame(0, $paciente->medicacionActual()->count());
    }

    public function test_medicacion_sin_nombre_es_422(): void
    {
        [$usuario] = $this->crearPaciente();

        $this->actingAs($usuario)->patchJson('/api/perfil', [
            'medicacionActual' => [['cantidad' => '850 mg']],
        ])->assertStatus(422);
    }

    public function test_valida_los_limites_clinicos(): void
    {
        [$usuario] = $this->crearPaciente();

        $this->actingAs($usuario)->patchJson('/api/perfil', ['pesoKg' => 500])
            ->assertStatus(422);

        $this->actingAs($usuario)->patchJson('/api/perfil', ['sexo' => 'x'])
            ->assertStatus(422);
    }

    public function test_cuenta_sin_fila_de_paciente_ignora_campos_clinicos(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@glucy.test',
            'password' => 'secret1234',
            'rol' => User::ROL_ADMIN,
        ]);

        $this->actingAs($admin)->patchJson('/api/perfil', [
            'name' => 'Admin Nuevo',
            'pesoKg' => 80,
        ])->assertOk()->assertJsonPath('name', 'Admin Nuevo');
    }

    public function test_exige_sesion(): void
    {
        $this->patchJson('/api/perfil', ['name' => 'X'])->assertStatus(401);
    }
}
