<?php

namespace Tests\Feature;

use App\Models\Clinica;
use App\Models\Doctor;
use App\Models\EstudioMedico;
use App\Models\Paciente;
use App\Models\TipoEstudio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contrato de POST /estudios-medicos/{id}/validar tal como lo consume la
 * seccion Estudios del panel (glucyfront).
 */
class ValidarEstudioTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $usuarioDoctor;

    private Doctor $doctor;

    private EstudioMedico $estudio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@glucy.test', 'password' => 'x', 'rol' => User::ROL_ADMIN]);

        $clinica = Clinica::create(['nombre' => 'San Rafael', 'direccion' => 'Calle 1', 'telefono' => '1', 'usuarioId' => $this->admin->id]);

        $this->usuarioDoctor = User::create(['name' => 'Carla', 'email' => 'carla@glucy.test', 'password' => 'x', 'rol' => User::ROL_DOCTOR]);
        $this->doctor = Doctor::create(['usuarioId' => $this->usuarioDoctor->id, 'clinicaId' => $clinica->id, 'matricula' => 'MP-1']);

        $usuarioPaciente = User::create(['name' => 'Maria', 'email' => 'maria@glucy.test', 'password' => 'x', 'rol' => User::ROL_PACIENTE]);
        $paciente = Paciente::create(['usuarioId' => $usuarioPaciente->id, 'clinicaId' => $clinica->id, 'fechaNacimiento' => '1968-03-04', 'tipoDiabetes' => 'DM2']);

        $tipo = TipoEstudio::create(['nombre' => 'HbA1c', 'unidad' => '%', 'rangoMin' => 4, 'rangoMax' => 6.5]);

        $this->estudio = EstudioMedico::create([
            'tipoEstudioId' => $tipo->id,
            'pacienteId' => $paciente->id,
            'fecha' => '2026-08-01',
            'valor' => 7.2,
            'estado' => 'pendiente',
        ]);
    }

    public function test_el_panel_lista_pendientes_con_paciente_tipo_y_archivo(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/estudios-medicos?estado=pendiente&porPagina=100')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.estado', 'pendiente')
            ->assertJsonPath('data.0.tipo_estudio.nombre', 'HbA1c')
            ->assertJsonPath('data.0.paciente.usuario.name', 'Maria')
            ->assertJsonStructure(['data' => [['id', 'archivo', 'intento', 'origen', 'fecha']]]);
    }

    public function test_admin_aprueba_un_estudio(): void
    {
        $this->actingAs($this->admin)
            ->postJson("/api/estudios-medicos/{$this->estudio->id}/validar", ['estado' => 'aprobado'])
            ->assertOk()
            ->assertJsonPath('estado', 'aprobado')
            ->assertJsonPath('motivoRechazo', null);

        $this->assertNotNull($this->estudio->fresh()->validadoEn);
    }

    public function test_doctor_de_la_clinica_rechaza_con_motivo(): void
    {
        $this->actingAs($this->usuarioDoctor)
            ->postJson("/api/estudios-medicos/{$this->estudio->id}/validar", [
                'estado' => 'rechazado',
                'motivoRechazo' => 'Imagen ilegible',
            ])
            ->assertOk()
            ->assertJsonPath('estado', 'rechazado')
            ->assertJsonPath('motivoRechazo', 'Imagen ilegible')
            ->assertJsonPath('validadoPor', $this->doctor->id);
    }

    public function test_rechazar_sin_motivo_es_422(): void
    {
        $this->actingAs($this->admin)
            ->postJson("/api/estudios-medicos/{$this->estudio->id}/validar", ['estado' => 'rechazado'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['motivoRechazo']);
    }

    public function test_marcar_en_revision_y_luego_aprobar(): void
    {
        $this->actingAs($this->admin)
            ->postJson("/api/estudios-medicos/{$this->estudio->id}/validar", ['estado' => 'en_revision'])
            ->assertOk()
            ->assertJsonPath('estado', 'en_revision');

        $this->actingAs($this->admin)
            ->postJson("/api/estudios-medicos/{$this->estudio->id}/validar", ['estado' => 'aprobado'])
            ->assertOk()
            ->assertJsonPath('estado', 'aprobado');
    }
}
