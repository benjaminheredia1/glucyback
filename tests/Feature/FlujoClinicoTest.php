<?php

namespace Tests\Feature;

use App\Models\Alerta;
use App\Models\Caso;
use App\Models\Ciclo;
use App\Models\Clinica;
use App\Models\Doctor;
use App\Models\Paciente;
use App\Models\Tratamiento;
use App\Models\User;
use Database\Seeders\PreguntaPrecalificacionSeeder;
use Database\Seeders\ReglaAlertaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlujoClinicoTest extends TestCase
{
    use RefreshDatabase;

    private User $usuarioPaciente;

    private Paciente $paciente;

    private User $usuarioDoctor;

    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@glucy.test',
            'password' => 'secret1234',
            'rol' => User::ROL_ADMIN,
        ]);

        $clinica = Clinica::create([
            'nombre' => 'San Rafael',
            'direccion' => 'Calle 1',
            'telefono' => '123',
            'usuarioId' => $admin->id,
        ]);

        $this->usuarioDoctor = User::create([
            'name' => 'Carla',
            'email' => 'carla@glucy.test',
            'password' => 'secret1234',
            'rol' => User::ROL_DOCTOR,
        ]);

        $this->doctor = Doctor::create([
            'usuarioId' => $this->usuarioDoctor->id,
            'clinicaId' => $clinica->id,
            'matricula' => 'MP-1234',
        ]);

        $this->usuarioPaciente = User::create([
            'name' => 'Maria',
            'email' => 'maria@glucy.test',
            'password' => 'secret1234',
            'rol' => User::ROL_PACIENTE,
        ]);

        $this->paciente = Paciente::create([
            'usuarioId' => $this->usuarioPaciente->id,
            'clinicaId' => $clinica->id,
            'fechaNacimiento' => '1968-03-04',
            'tipoDiabetes' => 'DM2',
        ]);
    }

    public function test_una_alarma_urgente_corta_la_precalificacion(): void
    {
        $this->seed(PreguntaPrecalificacionSeeder::class);

        $preguntas = $this->getJson('/api/precalificacion/preguntas')->assertOk()->json();

        $this->assertCount(9, $preguntas);
        // El endpoint publico no filtra cual respuesta dispara la alarma.
        $this->assertArrayNotHasKey('respuestaAlarma', $preguntas[0]);

        $respuestas = collect($preguntas)->map(fn ($p) => [
            'preguntaId' => $p['id'],
            // q4 son los sintomas de descompensacion aguda.
            'respuesta' => $p['codigo'] === 'q4' ? 'si' : ($p['codigo'] === 'q1' ? 'si' : 'no'),
        ])->all();

        $respuesta = $this->postJson('/api/precalificacion/evaluar', [
            'leadEmail' => 'lead@glucy.test',
            'respuestas' => $respuestas,
        ]);

        $respuesta->assertCreated();
        $this->assertSame('urgente', $respuesta->json('resultado'));
        $this->assertSame('síntomas de descompensación aguda', $respuesta->json('motivo'));
    }

    public function test_la_precalificacion_exige_responder_todas_las_preguntas(): void
    {
        $this->seed(PreguntaPrecalificacionSeeder::class);

        $preguntas = $this->getJson('/api/precalificacion/preguntas')->json();

        $this->postJson('/api/precalificacion/evaluar', [
            'respuestas' => [['preguntaId' => $preguntas[0]['id'], 'respuesta' => 'si']],
        ])->assertStatus(422);
    }

    public function test_un_valor_critico_genera_alerta_y_abre_caso_urgente(): void
    {
        $this->seed(ReglaAlertaSeeder::class);

        $respuesta = $this->actingAs($this->usuarioPaciente)->postJson('/api/mediciones', [
            'valor' => 268,
            'momento' => 'postprandial',
        ]);

        $respuesta->assertCreated();

        $alerta = Alerta::where('pacienteId', $this->paciente->id)->first();

        $this->assertNotNull($alerta);
        $this->assertSame('critica', $alerta->severidad);
        $this->assertSame('valor_critico', $alerta->tipo);

        $caso = Caso::find($alerta->casoId);

        $this->assertNotNull($caso);
        $this->assertSame('urgente', $caso->urgencia);
        $this->assertSame('alerta', $caso->tipo);
    }

    public function test_completar_el_ciclo_abre_el_caso_de_ajuste(): void
    {
        $ciclo = Ciclo::create([
            'pacienteId' => $this->paciente->id,
            'numero' => 1,
            'inicio' => now()->toDateString(),
            'fin' => now()->addDays(15)->toDateString(),
            'medicionesRequeridas' => 3,
        ]);

        foreach ([120, 118, 124] as $valor) {
            $this->actingAs($this->usuarioPaciente)
                ->postJson('/api/mediciones', ['valor' => $valor, 'momento' => 'ayunas'])
                ->assertCreated();
        }

        $ciclo->refresh();

        $this->assertSame(3, $ciclo->medicionesRegistradas);
        $this->assertSame('completo', $ciclo->estado);

        $this->assertDatabaseHas('casos', [
            'cicloId' => $ciclo->id,
            'tipo' => 'ajuste_ciclo',
            'estado' => 'abierto',
        ]);
    }

    public function test_un_tratamiento_firmado_no_se_edita_ni_se_borra(): void
    {
        $tratamiento = Tratamiento::create([
            'pacienteId' => $this->paciente->id,
            'descripcion' => 'Esquema inicial',
            'tratamientoDoctor' => 'Metformina 850 mg cada 12 h',
        ]);

        $this->actingAs($this->usuarioDoctor)
            ->postJson("/api/tratamientos/{$tratamiento->id}/firmar")
            ->assertOk()
            ->assertJsonPath('estado', 'firmado');

        $this->actingAs($this->usuarioDoctor)
            ->patchJson("/api/tratamientos/{$tratamiento->id}", ['descripcion' => 'Otro'])
            ->assertStatus(409);

        $this->actingAs($this->usuarioDoctor)
            ->deleteJson("/api/tratamientos/{$tratamiento->id}")
            ->assertStatus(409);

        $reemplazo = $this->actingAs($this->usuarioDoctor)
            ->postJson("/api/tratamientos/{$tratamiento->id}/reemplazar", [
                'descripcion' => 'Ajuste de ciclo 1',
                'tratamientoDoctor' => 'Metformina 850 mg cada 8 h',
            ]);

        $reemplazo->assertCreated();
        $this->assertSame(2, $reemplazo->json('version'));
        $this->assertSame($tratamiento->id, $reemplazo->json('reemplazaA'));
    }

    public function test_no_se_firma_un_tratamiento_sin_indicaciones_del_doctor(): void
    {
        $tratamiento = Tratamiento::create([
            'pacienteId' => $this->paciente->id,
            'descripcion' => 'Borrador de IA',
            'tratamientoAI' => 'Sugerencia generada',
        ]);

        $this->actingAs($this->usuarioDoctor)
            ->postJson("/api/tratamientos/{$tratamiento->id}/firmar")
            ->assertStatus(422);
    }

    public function test_las_escrituras_quedan_en_la_bitacora(): void
    {
        $this->actingAs($this->usuarioPaciente)
            ->postJson('/api/mediciones', ['valor' => 110, 'momento' => 'ayunas'])
            ->assertCreated();

        $this->assertDatabaseHas('audit_logs', [
            'usuarioId' => $this->usuarioPaciente->id,
            'entidad' => 'Medicion',
            'accion' => 'crear',
        ]);
    }
}
