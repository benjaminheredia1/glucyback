<?php

namespace Tests\Feature;

use App\Ai\Agents\ValidadorEstudios;
use App\Models\Archivo;
use App\Models\EstudioMedico;
use App\Models\Paciente;
use App\Models\TipoEstudio;
use App\Models\User;
use App\Support\AlmacenArchivos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * POST /archivos/subir con validacion por IA: el archivo del paciente pasa por
 * el agente ValidadorEstudios antes de guardarse. Si la IA lo reconoce como
 * estudio, los estudios detectados quedan aprobados sin revision manual; si lo
 * rechaza, la subida falla con 422; si la IA no responde, la subida sigue el
 * flujo manual de siempre.
 */
class ValidacionEstudiosIaTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private Paciente $paciente;

    private TipoEstudio $glucemia;

    private TipoEstudio $hba1c;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(AlmacenArchivos::DISCO);
        config(['ai.validar_estudios' => true]);

        $this->usuario = User::create([
            'name' => 'Paciente',
            'email' => 'paciente@glucy.test',
            'password' => 'secret1234',
            'rol' => User::ROL_PACIENTE,
        ]);

        $this->paciente = Paciente::create([
            'usuarioId' => $this->usuario->id,
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

    private function subir(): TestResponse
    {
        return $this->actingAs($this->usuario)->postJson('/api/archivos/subir', [
            'archivo' => UploadedFile::fake()->create('resultados.pdf', 100, 'application/pdf'),
        ]);
    }

    public function test_archivo_valido_aprueba_los_estudios_detectados(): void
    {
        ValidadorEstudios::fake([
            json_encode([
                'esEstudioValido' => true,
                'motivo' => null,
                'estudiosDetectados' => [
                    ['tipoEstudio' => 'Glucemia en ayunas', 'valor' => 92, 'unidad' => 'mg/dL', 'fecha' => '2026-08-01'],
                    ['tipoEstudio' => 'Hemoglobina glicosilada (HbA1c)', 'valor' => 5.4, 'unidad' => '%', 'fecha' => '2026-08-01'],
                ],
            ]),
        ]);

        $respuesta = $this->subir();

        $respuesta->assertCreated();

        // El veredicto viaja en la misma respuesta: el cliente sabe al
        // momento que tipos quedaron aprobados y no debe registrarlos como
        // pendientes.
        $respuesta->assertJsonCount(2, 'estudiosAprobados');
        $respuesta->assertJsonPath('estudiosAprobados.0.estado', 'aprobado');
        $respuesta->assertJsonPath('estudiosAprobados.0.tipo_estudio.nombre', 'Glucemia en ayunas');

        $this->assertSame(2, EstudioMedico::count());

        $estudio = EstudioMedico::where('tipoEstudioId', $this->glucemia->id)->first();
        $this->assertSame('aprobado', $estudio->estado);
        $this->assertSame($this->paciente->id, $estudio->pacienteId);
        $this->assertNull($estudio->validadoPor);
        $this->assertNotNull($estudio->validadoEn);
        $this->assertSame(Archivo::first()->id, $estudio->archivoId);
        $this->assertEqualsWithDelta(92, (float) $estudio->valor, 0.001);
    }

    public function test_un_estudio_pendiente_del_mismo_tipo_se_aprueba_en_vez_de_duplicarse(): void
    {
        $pendiente = EstudioMedico::create([
            'tipoEstudioId' => $this->glucemia->id,
            'pacienteId' => $this->paciente->id,
            'fecha' => '2026-07-01',
            'estado' => 'pendiente',
            'intento' => 1,
            'origen' => 'carga',
        ]);

        ValidadorEstudios::fake([
            json_encode([
                'esEstudioValido' => true,
                'motivo' => null,
                'estudiosDetectados' => [
                    ['tipoEstudio' => 'Glucemia en ayunas', 'valor' => 88, 'unidad' => 'mg/dL', 'fecha' => '2026-08-01'],
                ],
            ]),
        ]);

        $this->subir()->assertCreated();

        $this->assertSame(1, EstudioMedico::count());
        $this->assertSame('aprobado', $pendiente->fresh()->estado);
    }

    public function test_registrar_el_estudio_tras_la_subida_no_reabre_la_revision(): void
    {
        ValidadorEstudios::fake([
            json_encode([
                'esEstudioValido' => true,
                'motivo' => null,
                'estudiosDetectados' => [
                    ['tipoEstudio' => 'Glucemia en ayunas', 'valor' => 92, 'unidad' => 'mg/dL', 'fecha' => '2026-08-01'],
                ],
            ]),
        ]);

        $this->subir()->assertCreated();
        $archivo = Archivo::sole();

        // La app registra el estudio despues de subir: debe recibir el ya
        // aprobado por la IA, no una fila nueva en revision.
        $respuesta = $this->actingAs($this->usuario)->postJson('/api/estudios-medicos', [
            'tipoEstudioId' => $this->glucemia->id,
            'archivoId' => $archivo->id,
            'fecha' => '2026-08-01',
        ]);

        $respuesta->assertOk();
        $respuesta->assertJsonPath('estado', 'aprobado');

        $this->assertSame(1, EstudioMedico::count());
    }

    public function test_el_nombre_detectado_matchea_aunque_venga_sin_acentos_o_con_otra_capitalizacion(): void
    {
        $lipidico = TipoEstudio::create([
            'nombre' => 'Perfil lipídico', 'unidad' => 'mg/dL',
            'rangoMax' => 200, 'esObligatorio' => true, 'orden' => 5,
        ]);

        ValidadorEstudios::fake([
            json_encode([
                'esEstudioValido' => true,
                'motivo' => null,
                'estudiosDetectados' => [
                    // Sin acento y con otra capitalizacion: aun asi aprueba.
                    ['tipoEstudio' => 'perfil LIPIDICO ', 'valor' => 180, 'unidad' => 'mg/dL', 'fecha' => null],
                ],
            ]),
        ]);

        $this->subir()->assertCreated();

        $estudio = EstudioMedico::sole();
        $this->assertSame($lipidico->id, $estudio->tipoEstudioId);
        $this->assertSame('aprobado', $estudio->estado);
    }

    public function test_archivo_invalido_es_422_y_no_se_guarda_nada(): void
    {
        ValidadorEstudios::fake([
            json_encode([
                'esEstudioValido' => false,
                'motivo' => 'El documento no es un estudio de laboratorio.',
                'estudiosDetectados' => [],
            ]),
        ]);

        $respuesta = $this->subir();

        $respuesta->assertStatus(422);
        $this->assertSame(0, Archivo::count());
        $this->assertSame(0, EstudioMedico::count());
    }

    public function test_si_la_ia_falla_la_subida_sigue_el_flujo_manual(): void
    {
        ValidadorEstudios::fake(function (): void {
            throw new \RuntimeException('proveedor caido');
        });

        $respuesta = $this->subir();

        $respuesta->assertCreated();
        $respuesta->assertJsonCount(0, 'estudiosAprobados');

        $this->assertSame(1, Archivo::count());
        $this->assertSame(0, EstudioMedico::count());
    }

    public function test_con_el_toggle_apagado_no_se_llama_a_la_ia(): void
    {
        config(['ai.validar_estudios' => false]);

        // Sin fake: si la IA se llamara, el test fallaria por peticion real.
        $this->subir()->assertCreated();

        $this->assertSame(1, Archivo::count());
        $this->assertSame(0, EstudioMedico::count());
    }
}
