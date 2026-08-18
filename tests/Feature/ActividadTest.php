<?php

namespace Tests\Feature;

use App\Models\Clinica;
use App\Models\Doctor;
use App\Models\Medicamento;
use App\Models\Medicion;
use App\Models\Paciente;
use App\Models\PacienteMedicamento;
use App\Models\Toma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /actividad: historial del paciente para la pestaña "Actividad" de la
 * app: tomas ya marcadas (tomada/omitida) y mediciones de glucosa, mezcladas
 * y ordenadas de la mas reciente a la mas antigua.
 */
class ActividadTest extends TestCase
{
    use RefreshDatabase;

    private User $usuarioDoctor;
    private User $usuarioA;
    private User $usuarioB;
    private Paciente $pacienteA;
    private Paciente $pacienteB;

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

        $metformina = Medicamento::create(['nombre' => 'Metformina', 'concentracion' => '1000 mg']);
        $pm = PacienteMedicamento::create([
            'pacienteId' => $this->pacienteA->id,
            'medicamentoId' => $metformina->id,
            'dosis' => '1 comprimido',
            'frecuencia' => '2 veces al dia',
            'horarios' => ['08:00', '20:00'],
            'fechaInicio' => '2026-08-01',
        ]);

        // Hoy (2026-08-17): tomada 12:05Z, pendiente 00:00Z (no cuenta), omitida ayer.
        Toma::create(['pacienteMedicamentoId' => $pm->id, 'programadaEn' => '2026-08-17 12:00:00', 'tomadaEn' => '2026-08-17 12:05:00', 'estado' => 'tomada']);
        Toma::create(['pacienteMedicamentoId' => $pm->id, 'programadaEn' => '2026-08-18 00:00:00', 'estado' => 'pendiente']);
        $omitida = Toma::create(['pacienteMedicamentoId' => $pm->id, 'programadaEn' => '2026-08-16 12:00:00', 'estado' => 'omitida']);
        $omitida->timestamps = false;
        $omitida->forceFill(['updated_at' => '2026-08-16 12:30:00'])->save();

        Medicion::create(['pacienteId' => $this->pacienteA->id, 'valor' => 108, 'unidad' => 'mg/dL', 'momento' => 'ayunas', 'fuente' => 'manual', 'medidoEn' => '2026-08-17 11:10:00']);
        Medicion::create(['pacienteId' => $this->pacienteA->id, 'valor' => 132, 'unidad' => 'mg/dL', 'momento' => 'ayunas', 'fuente' => 'manual', 'medidoEn' => '2026-08-15 11:00:00']);

        // Ruido de otro paciente: no debe aparecer.
        Medicion::create(['pacienteId' => $this->pacienteB->id, 'valor' => 200, 'unidad' => 'mg/dL', 'momento' => 'ayunas', 'fuente' => 'manual', 'medidoEn' => '2026-08-17 12:00:00']);
    }

    public function test_mezcla_tomas_marcadas_y_mediciones_de_la_mas_reciente_a_la_mas_antigua(): void
    {
        $this->actingAs($this->usuarioA)
            ->getJson('/api/actividad')
            ->assertOk()
            ->assertJsonPath('total', 4)
            ->assertJsonPath('data.0.tipo', 'toma')
            ->assertJsonPath('data.0.estado', 'tomada')
            ->assertJsonPath('data.0.en', '2026-08-17T12:05:00.000000Z')
            ->assertJsonPath('data.0.medicamento', 'Metformina')
            ->assertJsonPath('data.0.dosis', '1 comprimido')
            ->assertJsonPath('data.0.programadaEn', '2026-08-17T12:00:00.000000Z')
            ->assertJsonPath('data.1.tipo', 'medicion')
            ->assertJsonPath('data.1.valor', 108)
            ->assertJsonPath('data.1.momento', 'ayunas')
            ->assertJsonPath('data.1.en', '2026-08-17T11:10:00.000000Z')
            ->assertJsonPath('data.2.tipo', 'toma')
            ->assertJsonPath('data.2.estado', 'omitida')
            ->assertJsonPath('data.2.en', '2026-08-16T12:30:00.000000Z')
            ->assertJsonPath('data.3.tipo', 'medicion')
            ->assertJsonPath('data.3.valor', 132);
    }

    public function test_desde_deja_fuera_lo_anterior(): void
    {
        $this->actingAs($this->usuarioA)
            ->getJson('/api/actividad?desde=2026-08-17')
            ->assertOk()
            ->assertJsonPath('total', 2);
    }

    public function test_pagina_en_memoria(): void
    {
        $this->actingAs($this->usuarioA)
            ->getJson('/api/actividad?porPagina=3&pagina=2')
            ->assertOk()
            ->assertJsonPath('total', 4)
            ->assertJsonPath('pagina', 2)
            ->assertJsonPath('porPagina', 3)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.valor', 132);
    }

    public function test_el_doctor_ve_la_de_un_paciente_visible_y_nada_sin_pacienteId(): void
    {
        $this->actingAs($this->usuarioDoctor)
            ->getJson('/api/actividad?pacienteId='.$this->pacienteA->id)
            ->assertOk()
            ->assertJsonPath('total', 4);

        $this->actingAs($this->usuarioDoctor)
            ->getJson('/api/actividad')
            ->assertOk()
            ->assertJsonPath('total', 0);
    }

    public function test_un_paciente_no_ve_la_actividad_de_otro(): void
    {
        $this->actingAs($this->usuarioB)
            ->getJson('/api/actividad?pacienteId='.$this->pacienteA->id)
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.valor', 200);
    }

    public function test_sin_sesion_es_401(): void
    {
        $this->getJson('/api/actividad')->assertUnauthorized();
    }
}
