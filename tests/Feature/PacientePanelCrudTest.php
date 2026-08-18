<?php

namespace Tests\Feature;

use App\Models\Clinica;
use App\Models\Doctor;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alta y edicion de pacientes desde el panel: `usuario` anidado en vez de
 * usuarioId, porque el paciente no tiene cuenta previa ni contrasena.
 */
class PacientePanelCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Clinica $clinica;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@glucy.test', 'password' => 'x', 'rol' => User::ROL_ADMIN]);
        $this->clinica = Clinica::create(['nombre' => 'San Rafael', 'direccion' => 'd', 'telefono' => 't', 'usuarioId' => $this->admin->id]);
    }

    private function cuerpo(array $extra = []): array
    {
        return array_replace_recursive([
            'usuario' => [
                'name' => 'Maria',
                'apellidoPaterno' => 'Lopez',
                'email' => 'Maria.Lopez@Ejemplo.com',
                'telefono' => '555',
            ],
            'clinicaId' => $this->clinica->id,
            'fechaNacimiento' => '1968-03-04',
            'sexo' => 'femenino',
            'tipoDiabetes' => 'DM2',
        ], $extra);
    }

    public function test_admin_crea_paciente_con_usuario_anidado(): void
    {
        $respuesta = $this->actingAs($this->admin)
            ->postJson('/api/pacientes', $this->cuerpo())
            ->assertCreated()
            ->assertJsonPath('usuario.name', 'Maria')
            ->assertJsonPath('usuario.email', 'maria.lopez@ejemplo.com')
            ->assertJsonPath('usuario.rol', User::ROL_PACIENTE)
            ->assertJsonPath('clinica.id', $this->clinica->id);

        $usuario = User::find($respuesta->json('usuarioId'));

        $this->assertNull($usuario->password);
        $this->assertSame(1, Paciente::count());
    }

    public function test_usuario_anidado_y_usuario_id_a_la_vez_es_422(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/pacientes', $this->cuerpo(['usuarioId' => $this->admin->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['usuarioId']);
    }

    public function test_correo_repetido_es_422(): void
    {
        $this->actingAs($this->admin)->postJson('/api/pacientes', $this->cuerpo())->assertCreated();

        $this->actingAs($this->admin)
            ->postJson('/api/pacientes', $this->cuerpo())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['usuario.email']);
    }

    public function test_alta_clasica_con_usuario_id_sigue_funcionando(): void
    {
        $usuario = User::create(['name' => 'Pepe', 'email' => 'pepe@ejemplo.com', 'password' => null, 'rol' => User::ROL_PACIENTE]);

        $this->actingAs($this->admin)
            ->postJson('/api/pacientes', [
                'usuarioId' => $usuario->id,
                'fechaNacimiento' => '1970-01-01',
                'tipoDiabetes' => 'DM1',
            ])
            ->assertCreated()
            ->assertJsonPath('usuarioId', $usuario->id);
    }

    public function test_actualizar_edita_datos_del_paciente_y_del_usuario(): void
    {
        $id = $this->actingAs($this->admin)->postJson('/api/pacientes', $this->cuerpo())->json('id');

        $this->actingAs($this->admin)
            ->putJson("/api/pacientes/{$id}", [
                'usuario' => ['name' => 'Maria Jose', 'email' => 'maria.lopez@ejemplo.com', 'telefono' => '999'],
                'tipoDiabetes' => 'DM1',
                'pesoKg' => 70.5,
            ])
            ->assertOk()
            ->assertJsonPath('usuario.name', 'Maria Jose')
            ->assertJsonPath('usuario.telefono', '999')
            ->assertJsonPath('tipoDiabetes', 'DM1');
    }

    public function test_doctor_crea_paciente_en_su_clinica_pero_no_en_otra(): void
    {
        $usuarioDoctor = User::create(['name' => 'Carla', 'email' => 'carla@glucy.test', 'password' => 'x', 'rol' => User::ROL_DOCTOR]);
        Doctor::create(['usuarioId' => $usuarioDoctor->id, 'clinicaId' => $this->clinica->id, 'matricula' => 'MP-1']);
        $otra = Clinica::create(['nombre' => 'Otra', 'direccion' => 'd', 'telefono' => 't', 'usuarioId' => $this->admin->id]);

        $this->actingAs($usuarioDoctor)->postJson('/api/pacientes', $this->cuerpo())->assertCreated();

        $this->actingAs($usuarioDoctor)
            ->postJson('/api/pacientes', $this->cuerpo(['usuario' => ['email' => 'otra@ejemplo.com'], 'clinicaId' => $otra->id]))
            ->assertForbidden();

        // El alta rechazada no puede dejar un User huerfano con ese correo:
        // el usuario anidado se crea dentro de la misma transaccion que el
        // paciente y se revierte con el.
        $this->assertDatabaseMissing('users', ['email' => 'otra@ejemplo.com']);
    }

    public function test_eliminar_paciente_conserva_el_usuario(): void
    {
        $respuesta = $this->actingAs($this->admin)->postJson('/api/pacientes', $this->cuerpo());

        $this->actingAs($this->admin)->deleteJson('/api/pacientes/'.$respuesta->json('id'))->assertNoContent();

        $this->assertSoftDeleted('pacientes', ['id' => $respuesta->json('id')]);
        $this->assertDatabaseHas('users', ['id' => $respuesta->json('usuarioId'), 'deleted_at' => null]);
    }
}
