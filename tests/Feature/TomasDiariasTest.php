<?php

namespace Tests\Feature;

use App\Models\Clinica;
use App\Models\Doctor;
use App\Models\Medicamento;
use App\Models\Paciente;
use App\Models\PacienteMedicamento;
use App\Models\Toma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /tomas?dia&zona materializa las tomas pendientes del dia del paciente a
 * partir de sus paciente_medicamentos con horarios. Idempotente y dentro del
 * alcance de siempre.
 */
class TomasDiariasTest extends TestCase
{
    use RefreshDatabase;

    private User $usuarioDoctor;

    private User $usuarioA;

    private User $usuarioB;

    private Paciente $pacienteA;

    private Paciente $pacienteB;

    private Medicamento $metformina;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::create(['name' => 'Admin', 'email' => 'admin@glucy.test', 'password' => 'x', 'rol' => User::ROL_ADMIN]);
        $clinica = Clinica::create(['nombre' => 'San Rafael', 'direccion' => 'Calle 1', 'telefono' => '1', 'usuarioId' => $admin->id]);
        $this->usuarioDoctor = User::create(['name' => 'Carla', 'email' => 'carla@glucy.test', 'password' => 'x', 'rol' => User::ROL_DOCTOR]);
        Doctor::create(['usuarioId' => $this->usuarioDoctor->id, 'clinicaId' => $clinica->id, 'matricula' => 'MP-1']);

        $this->usuarioA = User::create(['name' => 'Maria', 'email' => 'maria@glucy.test', 'password' => 'x', 'rol' => User::ROL_PACIENTE]);
        $this->pacienteA = Paciente::create(['usuarioId' => $this->usuarioA->id, 'clinicaId' => $clinica->id, 'fechaNacimiento' => '1968-03-04', 'tipoDiabetes' => 'DM2']);
        $this->usuarioB = User::create(['name' => 'Pedro', 'email' => 'pedro@glucy.test', 'password' => 'x', 'rol' => User::ROL_PACIENTE]);
        $this->pacienteB = Paciente::create(['usuarioId' => $this->usuarioB->id, 'clinicaId' => $clinica->id, 'fechaNacimiento' => '1970-01-01', 'tipoDiabetes' => 'DM2']);

        $this->metformina = Medicamento::create(['nombre' => 'Metformina', 'concentracion' => '1000 mg']);
    }

    private function medicamentoDe(Paciente $paciente, array $extra = []): PacienteMedicamento
    {
        return PacienteMedicamento::create(array_merge([
            'pacienteId' => $paciente->id,
            'medicamentoId' => $this->metformina->id,
            'dosis' => '1 comprimido',
            'frecuencia' => '2 veces al dia',
            'horarios' => ['08:00', '20:00'],
            'fechaInicio' => '2026-08-01',
            'activo' => true,
        ], $extra));
    }

    public function test_materializa_las_tomas_del_dia_en_la_zona_del_paciente(): void
    {
        $this->medicamentoDe($this->pacienteA);

        $respuesta = $this->actingAs($this->usuarioA)
            ->getJson('/api/tomas?dia=2026-08-17&zona=America/La_Paz&orden=programadaEn&direccion=asc')
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('data.0.estado', 'pendiente')
            // 08:00 en La Paz (UTC-4) son las 12:00 UTC.
            ->assertJsonPath('data.0.programadaEn', '2026-08-17T12:00:00.000000Z')
            ->assertJsonPath('data.1.programadaEn', '2026-08-18T00:00:00.000000Z')
            ->assertJsonPath('data.0.paciente_medicamento.medicamento.nombre', 'Metformina');

        $this->assertSame(2, Toma::count());
    }

    public function test_pedir_el_mismo_dia_dos_veces_no_duplica(): void
    {
        $this->medicamentoDe($this->pacienteA);

        $this->actingAs($this->usuarioA)->getJson('/api/tomas?dia=2026-08-17&zona=America/La_Paz')->assertOk();
        $this->actingAs($this->usuarioA)->getJson('/api/tomas?dia=2026-08-17&zona=America/La_Paz')
            ->assertOk()
            ->assertJsonPath('total', 2);

        $this->assertSame(2, Toma::count());
    }

    public function test_un_dia_distinto_genera_sus_propias_tomas(): void
    {
        $this->medicamentoDe($this->pacienteA);

        $this->actingAs($this->usuarioA)->getJson('/api/tomas?dia=2026-08-17&zona=UTC')->assertJsonPath('total', 2);
        $this->actingAs($this->usuarioA)->getJson('/api/tomas?dia=2026-08-18&zona=UTC')->assertJsonPath('total', 2);

        $this->assertSame(4, Toma::count());
    }

    public function test_medicamentos_inactivos_o_fuera_de_fechas_no_generan_tomas(): void
    {
        $this->medicamentoDe($this->pacienteA, ['activo' => false]);
        $this->medicamentoDe($this->pacienteA, ['fechaInicio' => '2026-08-18']);
        $this->medicamentoDe($this->pacienteA, ['fechaInicio' => '2026-07-01', 'fechaFin' => '2026-08-16']);

        $this->actingAs($this->usuarioA)
            ->getJson('/api/tomas?dia=2026-08-17&zona=UTC')
            ->assertOk()
            ->assertJsonPath('total', 0);

        $this->assertSame(0, Toma::count());
    }

    public function test_un_paciente_no_ve_ni_marca_las_tomas_de_otro(): void
    {
        $this->medicamentoDe($this->pacienteB);

        // B materializa las suyas.
        $this->actingAs($this->usuarioB)->getJson('/api/tomas?dia=2026-08-17&zona=UTC')->assertJsonPath('total', 2);
        $tomaDeB = Toma::firstOrFail();

        // A no las ve aunque pida el pacienteId de B (se ignora: un paciente es su propio alcance).
        $this->actingAs($this->usuarioA)
            ->getJson('/api/tomas?dia=2026-08-17&zona=UTC&pacienteId='.$this->pacienteB->id)
            ->assertOk()
            ->assertJsonPath('total', 0);

        $this->actingAs($this->usuarioA)
            ->postJson('/api/tomas/'.$tomaDeB->id.'/marcar', ['estado' => 'tomada'])
            ->assertNotFound();
    }

    public function test_el_doctor_materializa_y_lista_las_de_un_paciente_visible(): void
    {
        $this->medicamentoDe($this->pacienteA);

        $this->actingAs($this->usuarioDoctor)
            ->getJson('/api/tomas?dia=2026-08-17&zona=UTC&pacienteId='.$this->pacienteA->id)
            ->assertOk()
            ->assertJsonPath('total', 2);
    }

    public function test_marcar_como_tomada_fija_la_hora(): void
    {
        $this->medicamentoDe($this->pacienteA);
        $this->actingAs($this->usuarioA)->getJson('/api/tomas?dia=2026-08-17&zona=UTC');
        $toma = Toma::firstOrFail();

        $this->actingAs($this->usuarioA)
            ->postJson('/api/tomas/'.$toma->id.'/marcar', ['estado' => 'tomada'])
            ->assertOk()
            ->assertJsonPath('estado', 'tomada');

        $this->assertNotNull($toma->fresh()->tomadaEn);
    }

    public function test_una_zona_invalida_es_422(): void
    {
        $this->actingAs($this->usuarioA)
            ->getJson('/api/tomas?dia=2026-08-17&zona=Marte/Olympus')
            ->assertUnprocessable();
    }
}
