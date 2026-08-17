<?php

namespace Tests\Feature;

use App\Http\Controllers\ArchivoController;
use App\Models\Archivo;
use App\Models\Clinica;
use App\Models\Doctor;
use App\Models\EstudioMedico;
use App\Models\Paciente;
use App\Models\TipoEstudio;
use App\Models\User;
use App\Support\AlmacenArchivos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubidaArchivosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(AlmacenArchivos::DISCO);
    }

    /**
     * @return array{0: User, 1: Paciente}
     */
    private function crearPaciente(string $email, ?int $clinicaId = null): array
    {
        $usuario = User::create([
            'name' => 'Paciente',
            'email' => $email,
            'password' => 'secret1234',
            'rol' => User::ROL_PACIENTE,
        ]);

        $paciente = Paciente::create([
            'usuarioId' => $usuario->id,
            'clinicaId' => $clinicaId,
            'fechaNacimiento' => '1970-01-01',
            'tipoDiabetes' => 'DM2',
        ]);

        return [$usuario, $paciente];
    }

    private function crearClinica(): Clinica
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin'.uniqid().'@glucy.test',
            'password' => 'secret1234',
            'rol' => User::ROL_ADMIN,
        ]);

        return Clinica::create([
            'nombre' => 'Clinica '.uniqid(),
            'direccion' => 'Calle 1',
            'telefono' => '123',
            'usuarioId' => $admin->id,
        ]);
    }

    public function test_un_paciente_sube_un_estudio_y_queda_en_el_disco_privado(): void
    {
        [$usuario] = $this->crearPaciente('maria@glucy.test');

        $respuesta = $this->actingAs($usuario)->postJson('/api/archivos/subir', [
            'archivo' => UploadedFile::fake()->create('hba1c.pdf', 120, 'application/pdf'),
            'descripcion' => 'HbA1c de julio',
        ]);

        $respuesta->assertCreated()
            ->assertJsonPath('nombre', 'hba1c.pdf')
            ->assertJsonPath('disk', AlmacenArchivos::DISCO)
            ->assertJsonPath('esPrivado', true)
            ->assertJsonPath('usuarioId', $usuario->id);

        $archivo = Archivo::findOrFail($respuesta->json('id'));

        Storage::disk(AlmacenArchivos::DISCO)->assertExists($archivo->ruta);

        // El nombre original no se usa como ruta.
        $this->assertStringNotContainsString('hba1c', $archivo->ruta);
        $this->assertSame(64, strlen($archivo->hashSha256));
    }

    public function test_se_rechaza_un_tipo_de_archivo_no_permitido(): void
    {
        [$usuario] = $this->crearPaciente('maria@glucy.test');

        $this->actingAs($usuario)->postJson('/api/archivos/subir', [
            'archivo' => UploadedFile::fake()->create('script.php', 10, 'application/x-httpd-php'),
        ])->assertStatus(422)->assertJsonValidationErrors('archivo');

        $this->assertSame(0, Archivo::count());
    }

    public function test_se_rechaza_un_archivo_demasiado_grande(): void
    {
        [$usuario] = $this->crearPaciente('maria@glucy.test');

        $this->actingAs($usuario)->postJson('/api/archivos/subir', [
            'archivo' => UploadedFile::fake()->create('grande.pdf', AlmacenArchivos::MAX_KB + 1, 'application/pdf'),
        ])->assertStatus(422)->assertJsonValidationErrors('archivo');
    }

    public function test_subir_dos_veces_el_mismo_archivo_reutiliza_la_fila(): void
    {
        [$usuario] = $this->crearPaciente('maria@glucy.test');

        $contenido = UploadedFile::fake()->createWithContent('estudio.pdf', 'bytes-identicos');

        $primera = $this->actingAs($usuario)
            ->postJson('/api/archivos/subir', ['archivo' => $contenido])
            ->assertCreated();

        $segunda = $this->actingAs($usuario)->postJson('/api/archivos/subir', [
            'archivo' => UploadedFile::fake()->createWithContent('estudio.pdf', 'bytes-identicos'),
        ])->assertOk();

        $this->assertSame($primera->json('id'), $segunda->json('id'));
        $this->assertSame(1, Archivo::count());
    }

    public function test_un_paciente_no_descarga_el_archivo_de_otro(): void
    {
        [$usuarioA] = $this->crearPaciente('a@glucy.test');
        [$usuarioB] = $this->crearPaciente('b@glucy.test');

        $ajeno = $this->actingAs($usuarioB)->postJson('/api/archivos/subir', [
            'archivo' => UploadedFile::fake()->create('suyo.pdf', 10, 'application/pdf'),
        ])->json('id');

        $this->actingAs($usuarioA)->get("/api/archivos/{$ajeno}/descargar")->assertNotFound();
        $this->actingAs($usuarioA)->postJson("/api/archivos/{$ajeno}/enlace")->assertNotFound();
    }

    public function test_el_doctor_descarga_el_archivo_adjunto_al_estudio_de_su_paciente(): void
    {
        $clinica = $this->crearClinica();

        $usuarioDoctor = User::create([
            'name' => 'Carla',
            'email' => 'carla@glucy.test',
            'password' => 'secret1234',
            'rol' => User::ROL_DOCTOR,
        ]);

        Doctor::create([
            'usuarioId' => $usuarioDoctor->id,
            'clinicaId' => $clinica->id,
            'matricula' => 'MP-1',
        ]);

        [$usuarioPaciente, $paciente] = $this->crearPaciente('maria@glucy.test', $clinica->id);

        $archivoId = $this->actingAs($usuarioPaciente)->postJson('/api/archivos/subir', [
            'archivo' => UploadedFile::fake()->create('hba1c.pdf', 10, 'application/pdf'),
        ])->json('id');

        // Sin el estudio de por medio, el archivo del paciente no es suyo.
        $this->actingAs($usuarioDoctor)->get("/api/archivos/{$archivoId}/descargar")->assertNotFound();

        $tipo = TipoEstudio::create(['nombre' => 'HbA1c']);

        EstudioMedico::create([
            'tipoEstudioId' => $tipo->id,
            'pacienteId' => $paciente->id,
            'archivoId' => $archivoId,
            'fecha' => now()->toDateString(),
        ]);

        $this->actingAs($usuarioDoctor)->get("/api/archivos/{$archivoId}/descargar")->assertOk();
    }

    public function test_un_paciente_no_puede_adjuntar_el_archivo_de_otro_a_su_estudio(): void
    {
        [$usuarioA] = $this->crearPaciente('a@glucy.test');
        [$usuarioB] = $this->crearPaciente('b@glucy.test');

        $ajeno = $this->actingAs($usuarioB)->postJson('/api/archivos/subir', [
            'archivo' => UploadedFile::fake()->create('suyo.pdf', 10, 'application/pdf'),
        ])->json('id');

        $tipo = TipoEstudio::create(['nombre' => 'HbA1c']);

        $this->actingAs($usuarioA)->postJson('/api/estudios-medicos', [
            'tipoEstudioId' => $tipo->id,
            'archivoId' => $ajeno,
            'fecha' => now()->toDateString(),
        ])->assertForbidden();

        $this->assertSame(0, EstudioMedico::count());
    }

    public function test_el_enlace_firmado_sirve_el_archivo_y_caduca(): void
    {
        [$usuario] = $this->crearPaciente('maria@glucy.test');

        $archivoId = $this->actingAs($usuario)->postJson('/api/archivos/subir', [
            'archivo' => UploadedFile::fake()->create('hba1c.pdf', 10, 'application/pdf'),
        ])->json('id');

        $url = $this->actingAs($usuario)
            ->postJson("/api/archivos/{$archivoId}/enlace")
            ->assertOk()
            ->json('url');

        // Vale sin sesion: es la unica forma de usarlo desde un <Image src>.
        $this->get($url)->assertOk();

        $this->travel(ArchivoController::MINUTOS_ENLACE + 1)->minutes();
        $this->get($url)->assertForbidden();
    }

    public function test_una_firma_manipulada_no_abre_el_archivo(): void
    {
        [$usuario] = $this->crearPaciente('maria@glucy.test');

        $archivoId = $this->actingAs($usuario)->postJson('/api/archivos/subir', [
            'archivo' => UploadedFile::fake()->create('hba1c.pdf', 10, 'application/pdf'),
        ])->json('id');

        $url = $this->actingAs($usuario)->postJson("/api/archivos/{$archivoId}/enlace")->json('url');

        $this->get($url.'x')->assertForbidden();
        $this->get("/api/archivos/firmado/{$archivoId}")->assertForbidden();
    }

    public function test_no_se_elimina_un_archivo_que_respalda_un_estudio(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'root@glucy.test',
            'password' => 'secret1234',
            'rol' => User::ROL_ADMIN,
        ]);

        [$usuarioPaciente, $paciente] = $this->crearPaciente('maria@glucy.test');

        $archivoId = $this->actingAs($usuarioPaciente)->postJson('/api/archivos/subir', [
            'archivo' => UploadedFile::fake()->create('hba1c.pdf', 10, 'application/pdf'),
        ])->json('id');

        $tipo = TipoEstudio::create(['nombre' => 'HbA1c']);

        EstudioMedico::create([
            'tipoEstudioId' => $tipo->id,
            'pacienteId' => $paciente->id,
            'archivoId' => $archivoId,
            'fecha' => now()->toDateString(),
        ]);

        $this->actingAs($admin)->deleteJson("/api/archivos/{$archivoId}")->assertStatus(409);
    }

    public function test_la_subida_exige_sesion(): void
    {
        $this->postJson('/api/archivos/subir', [
            'archivo' => UploadedFile::fake()->create('hba1c.pdf', 10, 'application/pdf'),
        ])->assertUnauthorized();
    }
}
