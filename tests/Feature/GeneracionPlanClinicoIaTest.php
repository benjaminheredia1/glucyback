<?php

namespace Tests\Feature;

use App\Ai\Agents\GeneradorPlanClinico;
use App\Models\Clinica;
use App\Models\Diagnostico;
use App\Models\Doctor;
use App\Models\EstudioMedico;
use App\Models\Paciente;
use App\Models\TipoEstudio;
use App\Models\Tratamiento;
use App\Models\User;
use App\Support\GeneracionPlanClinicoIa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Al quedar aprobados todos los estudios obligatorios del paciente, la IA
 * genera un diagnostico y un tratamiento en borrador con aceptadoDoctor=false.
 * Se genera una sola vez y nunca rompe el flujo si la IA falla.
 */
class GeneracionPlanClinicoIaTest extends TestCase
{
    use RefreshDatabase;

    private Paciente $paciente;

    private TipoEstudio $glucemia;

    private TipoEstudio $hba1c;

    protected function setUp(): void
    {
        parent::setUp();

        config(['ai.generar_plan_clinico' => true]);

        $usuario = User::create([
            'name' => 'Paciente',
            'email' => 'paciente@glucy.test',
            'password' => 'secret1234',
            'rol' => User::ROL_PACIENTE,
        ]);

        $this->paciente = Paciente::create([
            'usuarioId' => $usuario->id,
            'fechaNacimiento' => '1970-01-01',
            'tipoDiabetes' => 'DM2',
        ]);

        $this->glucemia = TipoEstudio::create([
            'nombre' => 'Glucemia en ayunas', 'unidad' => 'mg/dL',
            'rangoMin' => 70, 'rangoMax' => 100, 'esObligatorio' => true, 'orden' => 1,
        ]);

        $this->hba1c = TipoEstudio::create([
            'nombre' => 'Hemoglobina glicosilada (HbA1c)', 'unidad' => '%',
            'rangoMin' => 4.0, 'rangoMax' => 5.7, 'esObligatorio' => true, 'orden' => 2,
        ]);
    }

    private function aprobarEstudio(TipoEstudio $tipo, float $valor): EstudioMedico
    {
        return EstudioMedico::create([
            'tipoEstudioId' => $tipo->id,
            'pacienteId' => $this->paciente->id,
            'fecha' => '2026-08-01',
            'valor' => $valor,
            'unidad' => $tipo->unidad,
            'estado' => 'aprobado',
            'validadoEn' => now(),
            'intento' => 1,
            'origen' => 'carga',
        ]);
    }

    private function planFake(): void
    {
        GeneradorPlanClinico::fake([
            json_encode([
                'diagnosticoResumen' => 'Diabetes tipo 2 con control glucemico aceptable.',
                'diagnosticoDetalle' => 'HbA1c 5.4% y glucemia 92 mg/dL dentro de rango.',
                'tratamientoResumen' => 'Plan de estilo de vida y control trimestral.',
                'tratamientoDetalle' => 'Dieta, ejercicio y HbA1c de control en 3 meses.',
            ]),
        ]);
    }

    public function test_con_todos_los_obligatorios_aprobados_genera_diagnostico_y_tratamiento(): void
    {
        $this->planFake();

        $this->aprobarEstudio($this->glucemia, 92);
        $this->aprobarEstudio($this->hba1c, 5.4);

        $resultado = app(GeneracionPlanClinicoIa::class)->generarSiCorresponde($this->paciente);

        $this->assertNotNull($resultado);

        $diagnostico = Diagnostico::sole();
        $this->assertSame($this->paciente->id, $diagnostico->pacienteId);
        $this->assertSame('borrador', $diagnostico->estado);
        $this->assertFalse($diagnostico->aceptadoDoctor);
        $this->assertNotNull($diagnostico->diagnosticoAI);

        $tratamiento = Tratamiento::sole();
        $this->assertSame('borrador', $tratamiento->estado);
        $this->assertFalse($tratamiento->aceptadoDoctor);
        $this->assertNotNull($tratamiento->tratamientoAI);
    }

    public function test_no_genera_si_falta_un_obligatorio(): void
    {
        $this->planFake();

        $this->aprobarEstudio($this->glucemia, 92);

        $this->assertNull(app(GeneracionPlanClinicoIa::class)->generarSiCorresponde($this->paciente));
        $this->assertSame(0, Diagnostico::count());
        $this->assertSame(0, Tratamiento::count());
    }

    public function test_no_duplica_si_el_paciente_ya_tiene_diagnostico(): void
    {
        $this->planFake();

        $this->aprobarEstudio($this->glucemia, 92);
        $this->aprobarEstudio($this->hba1c, 5.4);

        Diagnostico::create([
            'pacienteId' => $this->paciente->id,
            'descripcion' => 'Ya existente',
            'estado' => 'borrador',
            'version' => 1,
        ]);

        $this->assertNull(app(GeneracionPlanClinicoIa::class)->generarSiCorresponde($this->paciente));
        $this->assertSame(1, Diagnostico::count());
        $this->assertSame(0, Tratamiento::count());
    }

    public function test_un_nuevo_ciclo_de_estudios_genera_la_version_siguiente(): void
    {
        $this->planFake();

        $this->aprobarEstudio($this->glucemia, 92);
        $this->aprobarEstudio($this->hba1c, 5.4);

        app(GeneracionPlanClinicoIa::class)->generarSiCorresponde($this->paciente);
        $this->assertSame(1, Diagnostico::count());

        // Sin estudios nuevos no se repite, aunque se vuelva a invocar.
        $this->planFake();
        $this->assertNull(app(GeneracionPlanClinicoIa::class)->generarSiCorresponde($this->paciente));
        $this->assertSame(1, Diagnostico::count());

        // El doctor pide estudios de nuevo: llega una aprobacion posterior.
        $this->travel(1)->hours();
        $this->aprobarEstudio($this->hba1c, 6.1);

        $this->planFake();
        $resultado = app(GeneracionPlanClinicoIa::class)->generarSiCorresponde($this->paciente);

        $this->assertNotNull($resultado);
        $this->assertSame(2, Diagnostico::count());
        $this->assertSame(2, Tratamiento::count());
        $this->assertSame(2, $resultado['diagnostico']->version);
        $this->assertSame(2, $resultado['tratamiento']->version);
        $this->assertFalse($resultado['diagnostico']->aceptadoDoctor);
        $this->assertFalse($resultado['tratamiento']->aceptadoDoctor);
    }

    public function test_si_la_ia_falla_no_crea_nada_ni_revienta(): void
    {
        GeneradorPlanClinico::fake(function (): void {
            throw new \RuntimeException('proveedor caido');
        });

        $this->aprobarEstudio($this->glucemia, 92);
        $this->aprobarEstudio($this->hba1c, 5.4);

        $this->assertNull(app(GeneracionPlanClinicoIa::class)->generarSiCorresponde($this->paciente));
        $this->assertSame(0, Diagnostico::count());
    }

    public function test_la_validacion_manual_del_doctor_tambien_dispara_la_generacion(): void
    {
        $this->planFake();

        $usuarioDoctor = User::create([
            'name' => 'Doctor',
            'email' => 'doctor@glucy.test',
            'password' => 'secret1234',
            'rol' => User::ROL_DOCTOR,
        ]);

        $clinica = Clinica::create([
            'nombre' => 'Central',
            'direccion' => 'Calle 1',
            'telefono' => '123',
            'usuarioId' => $usuarioDoctor->id,
        ]);

        Doctor::create([
            'usuarioId' => $usuarioDoctor->id,
            'clinicaId' => $clinica->id,
            'matricula' => 'MAT-1',
        ]);

        $this->paciente->update(['clinicaId' => $clinica->id]);

        $this->aprobarEstudio($this->glucemia, 92);

        $pendiente = EstudioMedico::create([
            'tipoEstudioId' => $this->hba1c->id,
            'pacienteId' => $this->paciente->id,
            'fecha' => '2026-08-01',
            'valor' => 5.4,
            'unidad' => '%',
            'estado' => 'pendiente',
            'intento' => 1,
            'origen' => 'carga',
        ]);

        $this->actingAs($usuarioDoctor)
            ->postJson("/api/estudios-medicos/{$pendiente->id}/validar", ['estado' => 'aprobado'])
            ->assertOk();

        $this->assertSame(1, Diagnostico::count());
        $this->assertSame(1, Tratamiento::count());
        $this->assertFalse(Diagnostico::sole()->aceptadoDoctor);
    }

    public function test_el_doctor_acepta_con_patch_aceptado_doctor(): void
    {
        $this->planFake();

        $this->aprobarEstudio($this->glucemia, 92);
        $this->aprobarEstudio($this->hba1c, 5.4);

        app(GeneracionPlanClinicoIa::class)->generarSiCorresponde($this->paciente);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@glucy.test',
            'password' => 'secret1234',
            'rol' => User::ROL_ADMIN,
        ]);

        $diagnostico = Diagnostico::sole();

        $this->actingAs($admin)
            ->patchJson("/api/diagnosticos/{$diagnostico->id}", ['aceptadoDoctor' => true])
            ->assertOk();

        $this->assertTrue($diagnostico->fresh()->aceptadoDoctor);
    }
}
