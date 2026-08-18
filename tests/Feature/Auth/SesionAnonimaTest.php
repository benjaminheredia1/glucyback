<?php

namespace Tests\Feature\Auth;

use App\Models\Paciente;
use App\Models\TipoEstudio;
use App\Models\User;
use App\Support\AlmacenArchivos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SesionAnonimaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_alta_anonima_crea_usuario_y_paciente_y_devuelve_token(): void
    {
        $respuesta = $this->postJson('/api/auth/anonimo', ['dispositivo' => 'iphone-anon']);

        $respuesta->assertCreated()
            ->assertJsonStructure(['token', 'usuario' => ['id', 'rol', 'esTemporal', 'paciente']])
            ->assertJsonPath('usuario.rol', User::ROL_PACIENTE)
            ->assertJsonPath('usuario.email', null)
            ->assertJsonPath('usuario.esTemporal', true)
            ->assertJsonPath('usuario.name', User::NOMBRE_TEMPORAL);

        $id = $respuesta->json('usuario.id');

        $this->assertDatabaseHas('users', ['id' => $id, 'email' => null, 'auth0Sub' => null, 'rol' => User::ROL_PACIENTE]);
        $this->assertDatabaseHas('pacientes', ['usuarioId' => $id]);
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $id, 'name' => 'iphone-anon']);
        $this->assertDatabaseHas('audit_logs', [
            'usuarioId' => $id,
            'entidad' => 'User',
            'entidadId' => $id,
            'accion' => 'crear-anonimo',
        ]);
    }

    public function test_el_token_anonimo_sirve_para_usar_la_api(): void
    {
        $alta = $this->postJson('/api/auth/anonimo')->assertCreated();

        $this->withToken($alta->json('token'))
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id', $alta->json('usuario.id'))
            ->assertJsonPath('esTemporal', true);
    }

    // sanctum.expiration vence todo token a las 24 h y la app lo renueva por
    // Auth0. El anonimo no tiene Auth0: su token tiene que sobrevivir.
    public function test_el_token_anonimo_no_caduca_a_las_24_horas(): void
    {
        $alta = $this->postJson('/api/auth/anonimo')->assertCreated();

        $this->travel(2)->days();

        $this->withToken($alta->json('token'))->getJson('/api/user')->assertOk();
    }

    public function test_el_token_de_una_cuenta_real_sigue_caducando_a_las_24_horas(): void
    {
        $usuario = User::factory()->create();
        $token = $usuario->createToken('api')->plainTextToken;

        $this->travel(2)->days();

        $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
    }

    public function test_el_anonimo_sube_un_estudio_a_su_propio_nombre(): void
    {
        Storage::fake(AlmacenArchivos::DISCO);

        $alta = $this->postJson('/api/auth/anonimo')->assertCreated();
        $token = $alta->json('token');
        $pacienteId = Paciente::where('usuarioId', $alta->json('usuario.id'))->sole()->id;

        [, $otro] = $this->pacienteRegistrado('otra@glucy.test');

        $archivoId = $this->withToken($token)->postJson('/api/archivos/subir', [
            'archivo' => UploadedFile::fake()->create('hba1c.pdf', 10, 'application/pdf'),
        ])->assertCreated()->json('id');

        $tipo = TipoEstudio::create(['nombre' => 'HbA1c']);

        // Aunque mande el pacienteId de otro, el estudio queda a su nombre
        // (forzarPacientePropio en EstudioMedicoController).
        $this->withToken($token)->postJson('/api/estudios-medicos', [
            'tipoEstudioId' => $tipo->id,
            'pacienteId' => $otro->id,
            'archivoId' => $archivoId,
            'fecha' => now()->toDateString(),
        ])->assertCreated()->assertJsonPath('pacienteId', $pacienteId);
    }

    public function test_el_anonimo_solo_ve_su_propio_paciente(): void
    {
        $alta = $this->postJson('/api/auth/anonimo')->assertCreated();
        $propio = Paciente::where('usuarioId', $alta->json('usuario.id'))->sole();

        $this->pacienteRegistrado('otra@glucy.test');

        $ids = collect($this->withToken($alta->json('token'))->getJson('/api/pacientes')->assertOk()->json('data'))
            ->pluck('id')
            ->all();

        $this->assertSame([$propio->id], $ids);
    }

    public function test_dos_altas_seguidas_crean_dos_anonimos_distintos(): void
    {
        $a = $this->postJson('/api/auth/anonimo')->assertCreated()->json('usuario.id');
        $b = $this->postJson('/api/auth/anonimo')->assertCreated()->json('usuario.id');

        $this->assertNotSame($a, $b);
        $this->assertSame(2, User::whereNull('email')->count());
    }

    /**
     * @return array{0: User, 1: Paciente}
     */
    private function pacienteRegistrado(string $email): array
    {
        $usuario = User::factory()->create(['email' => $email, 'rol' => User::ROL_PACIENTE]);
        $paciente = Paciente::create(['usuarioId' => $usuario->id]);

        return [$usuario, $paciente];
    }
}
