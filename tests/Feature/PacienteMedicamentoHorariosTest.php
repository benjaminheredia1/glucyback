<?php

namespace Tests\Feature;

use App\Models\Clinica;
use App\Models\Doctor;
use App\Models\Medicamento;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `horarios` en paciente_medicamentos: la app genera las tomas del dia a
 * partir de esta lista, asi que tiene que venir bien formada.
 */
class PacienteMedicamentoHorariosTest extends TestCase
{
    use RefreshDatabase;

    private User $usuarioDoctor;
    private Paciente $paciente;
    private Medicamento $medicamento;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::create(['name' => 'Admin', 'email' => 'admin@glucy.test', 'password' => 'x', 'rol' => User::ROL_ADMIN]);
        $clinica = Clinica::create(['nombre' => 'San Rafael', 'direccion' => 'Calle 1', 'telefono' => '1', 'usuarioId' => $admin->id]);
        $this->usuarioDoctor = User::create(['name' => 'Carla', 'email' => 'carla@glucy.test', 'password' => 'x', 'rol' => User::ROL_DOCTOR]);
        Doctor::create(['usuarioId' => $this->usuarioDoctor->id, 'clinicaId' => $clinica->id, 'matricula' => 'MP-1']);
        $usuarioPaciente = User::create(['name' => 'Maria', 'email' => 'maria@glucy.test', 'password' => 'x', 'rol' => User::ROL_PACIENTE]);
        $this->paciente = Paciente::create(['usuarioId' => $usuarioPaciente->id, 'clinicaId' => $clinica->id, 'fechaNacimiento' => '1968-03-04', 'tipoDiabetes' => 'DM2']);
        $this->medicamento = Medicamento::create(['nombre' => 'Metformina', 'concentracion' => '1000 mg']);
    }

    private function cuerpo(array $extra = []): array
    {
        return array_merge([
            'pacienteId' => $this->paciente->id,
            'medicamentoId' => $this->medicamento->id,
            'dosis' => '1 comprimido',
            'frecuencia' => '2 veces al dia',
            'fechaInicio' => '2026-08-17',
            'horarios' => ['08:00', '13:00'],
        ], $extra);
    }

    public function test_el_doctor_crea_con_horarios_y_vuelven_como_lista(): void
    {
        $this->actingAs($this->usuarioDoctor)
            ->postJson('/api/paciente-medicamentos', $this->cuerpo())
            ->assertCreated()
            ->assertJsonPath('horarios', ['08:00', '13:00'])
            ->assertJsonPath('medicamento.nombre', 'Metformina');
    }

    public function test_sin_horarios_no_se_puede_crear(): void
    {
        $this->actingAs($this->usuarioDoctor)
            ->postJson('/api/paciente-medicamentos', $this->cuerpo(['horarios' => null]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['horarios']);
    }

    public function test_un_horario_mal_formado_se_rechaza(): void
    {
        $this->actingAs($this->usuarioDoctor)
            ->postJson('/api/paciente-medicamentos', $this->cuerpo(['horarios' => ['8h']]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['horarios.0']);
    }

    public function test_mas_de_seis_horarios_se_rechaza(): void
    {
        $this->actingAs($this->usuarioDoctor)
            ->postJson('/api/paciente-medicamentos', $this->cuerpo([
                'horarios' => ['06:00', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00'],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['horarios']);
    }

    public function test_horarios_repetidos_se_rechazan(): void
    {
        $this->actingAs($this->usuarioDoctor)
            ->postJson('/api/paciente-medicamentos', $this->cuerpo(['horarios' => ['08:00', '08:00']]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['horarios.0']);
    }
}
