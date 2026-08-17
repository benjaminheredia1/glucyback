<?php

namespace Tests\Feature;

use App\Models\Clinica;
use App\Models\Paciente;
use App\Models\Precalificacion;
use App\Models\User;
use App\Support\Auth0\PerfilAuth0;
use App\Support\Auth0\VerificadorAuth0;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PrecalificacionVincularTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prueba end-to-end del bug original (User sin Paciente en el alta por
     * Auth0): el helper paciente() de abajo construye el Paciente a mano, lo
     * que hubiera ocultado el bug igual que lo escondio el codigo original.
     * Aqui el usuario nace del intercambio real /api/auth/auth0, sin atajos.
     */
    public function test_un_usuario_creado_por_el_intercambio_de_auth0_puede_vincular_su_precalificacion(): void
    {
        $this->app->instance(VerificadorAuth0::class, new class implements VerificadorAuth0
        {
            public function verificar(string $accessToken): PerfilAuth0
            {
                return new PerfilAuth0('auth0|nueva-paciente', 'nueva@ejemplo.com', true, 'Ana Ibarra');
            }
        });

        $token = $this->postJson('/api/auth/auth0', ['accessToken' => 'da-igual'])
            ->assertOk()
            ->json('token');

        $precalificacion = Precalificacion::create([
            'leadEmail' => 'nueva@ejemplo.com',
            'resultado' => 'apto',
            'versionCuestionario' => 1,
            'respondidoEn' => now(),
        ]);

        $usuario = User::where('email', 'nueva@ejemplo.com')->sole();
        $paciente = Paciente::where('usuarioId', $usuario->id)->sole();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/precalificaciones/{$precalificacion->id}/vincular")
            ->assertOk()
            ->assertJsonPath('pacienteId', $paciente->id);

        $this->assertSame($paciente->id, $precalificacion->fresh()->pacienteId);
    }

    public function test_el_paciente_vincula_su_precalificacion_anonima(): void
    {
        [$usuario, $paciente] = $this->paciente('maria@ejemplo.com');

        $precalificacion = Precalificacion::create([
            'leadEmail' => 'maria@ejemplo.com',
            'resultado' => 'apto',
            'versionCuestionario' => 1,
            'respondidoEn' => now(),
        ]);

        Sanctum::actingAs($usuario);

        $this->postJson("/api/precalificaciones/{$precalificacion->id}/vincular")
            ->assertOk()
            ->assertJsonPath('pacienteId', $paciente->id);

        $this->assertSame($paciente->id, $precalificacion->fresh()->pacienteId);
    }

    // Quien reclamo que respuestas clinicas, y cuando, tiene que quedar reconstruible:
    // sin esto un vinculo disputado no se podria auditar despues del hecho.
    public function test_vincular_deja_registro_en_la_bitacora(): void
    {
        [$usuario, $paciente] = $this->paciente('maria@ejemplo.com');

        $precalificacion = Precalificacion::create([
            'leadEmail' => 'maria@ejemplo.com',
            'resultado' => 'apto',
            'versionCuestionario' => 1,
            'respondidoEn' => now(),
        ]);

        Sanctum::actingAs($usuario);

        $this->postJson("/api/precalificaciones/{$precalificacion->id}/vincular")->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'usuarioId' => $usuario->id,
            'entidad' => 'Precalificacion',
            'entidadId' => $precalificacion->id,
            'accion' => 'vincular',
        ]);
    }

    public function test_rechaza_vincular_la_precalificacion_de_otro(): void
    {
        [$usuario] = $this->paciente('maria@ejemplo.com');

        $ajena = Precalificacion::create([
            'leadEmail' => 'jorge@ejemplo.com',
            'resultado' => 'apto',
            'versionCuestionario' => 1,
            'respondidoEn' => now(),
        ]);

        Sanctum::actingAs($usuario);

        $this->postJson("/api/precalificaciones/{$ajena->id}/vincular")->assertForbidden();

        $this->assertNull($ajena->fresh()->pacienteId);
    }

    public function test_rechaza_una_precalificacion_sin_lead_email(): void
    {
        [$usuario] = $this->paciente('maria@ejemplo.com');

        $huerfana = Precalificacion::create([
            'resultado' => 'apto',
            'versionCuestionario' => 1,
            'respondidoEn' => now(),
        ]);

        Sanctum::actingAs($usuario);

        $this->postJson("/api/precalificaciones/{$huerfana->id}/vincular")->assertForbidden();
    }

    public function test_rechaza_una_precalificacion_ya_vinculada(): void
    {
        [$usuario, $paciente] = $this->paciente('maria@ejemplo.com');

        $vinculada = Precalificacion::create([
            'pacienteId' => $paciente->id,
            'leadEmail' => 'maria@ejemplo.com',
            'resultado' => 'apto',
            'versionCuestionario' => 1,
            'respondidoEn' => now(),
        ]);

        Sanctum::actingAs($usuario);

        $this->postJson("/api/precalificaciones/{$vinculada->id}/vincular")->assertStatus(409);
    }

    public function test_el_cotejo_del_correo_ignora_mayusculas(): void
    {
        [$usuario, $paciente] = $this->paciente('maria@ejemplo.com');

        $precalificacion = Precalificacion::create([
            'leadEmail' => 'Maria@Ejemplo.com',
            'resultado' => 'apto',
            'versionCuestionario' => 1,
            'respondidoEn' => now(),
        ]);

        Sanctum::actingAs($usuario);

        $this->postJson("/api/precalificaciones/{$precalificacion->id}/vincular")->assertOk();

        $this->assertSame($paciente->id, $precalificacion->fresh()->pacienteId);
    }

    // El publico de /precalificacion/evaluar pasa por TrimStrings (middleware global),
    // asi que en la practica un leadEmail nunca llega con espacios por esa via. Pero un
    // registro sembrado por otra ruta (semilla, importacion) si podria tenerlos, y la
    // credencial no deberia fallar por eso: se tolera el espacio igual que en Task 4.
    public function test_el_cotejo_del_correo_ignora_espacios_alrededor(): void
    {
        [$usuario, $paciente] = $this->paciente('maria@ejemplo.com');

        $precalificacion = Precalificacion::create([
            'leadEmail' => '  maria@ejemplo.com  ',
            'resultado' => 'apto',
            'versionCuestionario' => 1,
            'respondidoEn' => now(),
        ]);

        Sanctum::actingAs($usuario);

        $this->postJson("/api/precalificaciones/{$precalificacion->id}/vincular")->assertOk();

        $this->assertSame($paciente->id, $precalificacion->fresh()->pacienteId);
    }

    public function test_un_usuario_sin_perfil_de_paciente_no_puede_vincular(): void
    {
        $usuario = User::factory()->create([
            'email' => 'maria@ejemplo.com',
            'rol' => User::ROL_PACIENTE,
        ]);

        $precalificacion = Precalificacion::create([
            'leadEmail' => 'maria@ejemplo.com',
            'resultado' => 'apto',
            'versionCuestionario' => 1,
            'respondidoEn' => now(),
        ]);

        Sanctum::actingAs($usuario);

        $this->postJson("/api/precalificaciones/{$precalificacion->id}/vincular")->assertStatus(422);
    }

    public function test_la_ruta_exige_sesion(): void
    {
        $precalificacion = Precalificacion::create([
            'leadEmail' => 'maria@ejemplo.com',
            'resultado' => 'apto',
            'versionCuestionario' => 1,
            'respondidoEn' => now(),
        ]);

        $this->postJson("/api/precalificaciones/{$precalificacion->id}/vincular")
            ->assertUnauthorized();
    }

    /**
     * @return array{0: User, 1: Paciente}
     *
     * Columnas copiadas de FlujoClinicoTest::setUp(), que ya monta este mismo
     * escenario contra las migraciones reales: nombre/direccion/telefono son
     * obligatorias en clinicas, fechaNacimiento/tipoDiabetes lo son en pacientes.
     */
    private function paciente(string $email): array
    {
        $usuario = User::factory()->create(['email' => $email, 'rol' => User::ROL_PACIENTE]);

        $clinica = Clinica::create([
            'usuarioId' => $usuario->id,
            'nombre' => 'Clinica de prueba',
            'direccion' => 'Calle 1',
            'telefono' => '123',
        ]);

        $paciente = Paciente::create([
            'usuarioId' => $usuario->id,
            'clinicaId' => $clinica->id,
            'fechaNacimiento' => '1968-03-04',
            'tipoDiabetes' => 'DM2',
        ]);

        return [$usuario, $paciente];
    }
}
