<?php

namespace Tests\Feature\Auth;

use App\Models\Paciente;
use App\Models\User;
use App\Support\Auth0\Auth0NoDisponible;
use App\Support\Auth0\PerfilAuth0;
use App\Support\Auth0\TokenAuth0Invalido;
use App\Support\Auth0\VerificadorAuth0;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Auth0LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_sub_nuevo_crea_un_paciente(): void
    {
        $this->fingirPerfil(new PerfilAuth0('auth0|nuevo', 'nueva@ejemplo.com', true, 'Ana Ibarra'));

        $respuesta = $this->postJson('/api/auth/auth0', [
            'accessToken' => 'da-igual',
            'dispositivo' => 'iphone-ana',
        ]);

        $respuesta->assertOk()
            ->assertJsonStructure(['token', 'usuario' => ['id', 'email', 'rol']])
            ->assertJsonPath('usuario.rol', User::ROL_PACIENTE)
            ->assertJsonPath('usuario.email', 'nueva@ejemplo.com');

        $this->assertDatabaseHas('users', [
            'email' => 'nueva@ejemplo.com',
            'auth0Sub' => 'auth0|nuevo',
            'rol' => User::ROL_PACIENTE,
        ]);

        $usuario = User::where('email', 'nueva@ejemplo.com')->sole();

        $this->assertNull($usuario->password);

        // El bug original: el alta creaba User pero nunca Paciente, y por eso
        // vincular() (PrecalificacionController) abortaba 422 en todo signup
        // real, porque Alcance::pacienteId() no encontraba ningun paciente.
        $this->assertDatabaseHas('pacientes', ['usuarioId' => $usuario->id]);

        $this->assertDatabaseHas('audit_logs', [
            'usuarioId' => $usuario->id,
            'entidad' => 'User',
            'entidadId' => $usuario->id,
            'accion' => 'crear',
        ]);
    }

    public function test_un_sub_nuevo_sin_email_verificado_no_crea_cuenta(): void
    {
        // Sin este guard, un atacante podria declarar en Auth0 un correo ajeno
        // sin verificar, conseguir que se le cree una cuenta de paciente para
        // ese correo y luego usarla en vincular() para leer el cuestionario
        // clinico anonimo (leadEmail) de la victima real.
        $this->fingirPerfil(new PerfilAuth0('auth0|impostor', 'victima@ejemplo.com', false, 'Impostor'));

        $this->postJson('/api/auth/auth0', ['accessToken' => 'da-igual'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Auth0 no ha verificado este correo para crear una cuenta.');

        $this->assertSame(0, User::count());
        $this->assertSame(0, Paciente::count());
    }

    public function test_rechaza_una_cuenta_dada_de_baja_por_auth0_sub_aunque_el_correo_cambie(): void
    {
        // El guard por email no la atraparia si Auth0 reporta un correo
        // distinto al que quedo guardado en la baja; el sub si sigue igual.
        // Sin el fix, esto caia en User::create() y explotaba contra el
        // indice unico de auth0Sub (500) en vez del 403 esperado.
        $borrado = User::factory()->conAuth0('auth0|reciclado')->create(['email' => 'antiguo@ejemplo.com']);
        $borrado->delete();

        $this->fingirPerfil(new PerfilAuth0('auth0|reciclado', 'nuevo@ejemplo.com', true, 'Quien sea'));

        $this->postJson('/api/auth/auth0', ['accessToken' => 'da-igual'])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Esta cuenta esta dada de baja. Contacta con soporte.');

        $this->assertSame(1, User::withTrashed()->count());
    }

    public function test_el_guard_de_baja_no_escapa_al_scope_de_solo_borrados(): void
    {
        // Prueba directa del patron SQL de resolverUsuario(): un where()
        // anidado (onlyTrashed()->where(fn => ...orWhere...)) tiene que dejar
        // la condicion "solo borrados" combinada con AND, no con OR. Un
        // ->where('email', ..)->orWhere('auth0Sub', ..) suelto (sin anidar)
        // rompe esa precedencia y este mismo assert daria true de forma
        // incorrecta para un usuario activo.
        User::factory()->conAuth0('auth0|activo')->create(['email' => 'activo@ejemplo.com']);

        $existeEnBorrados = User::onlyTrashed()->where(function ($consulta) {
            $consulta->where('email', 'nadie@ejemplo.com')->orWhere('auth0Sub', 'auth0|activo');
        })->exists();

        $this->assertFalse($existeEnBorrados);
    }

    public function test_un_sub_conocido_reusa_la_cuenta_y_no_cambia_el_rol(): void
    {
        $doctor = User::factory()->conAuth0('auth0|doc')->create(['rol' => User::ROL_DOCTOR]);

        $this->fingirPerfil(new PerfilAuth0('auth0|doc', $doctor->email, true, 'Dr. Medina'));

        $this->postJson('/api/auth/auth0', ['accessToken' => 'da-igual'])
            ->assertOk()
            ->assertJsonPath('usuario.id', $doctor->id)
            ->assertJsonPath('usuario.rol', User::ROL_DOCTOR);

        $this->assertSame(1, User::count());
    }

    public function test_vincula_por_email_verificado_a_una_cuenta_existente(): void
    {
        $existente = User::factory()->create([
            'email' => 'maria@ejemplo.com',
            'rol' => User::ROL_DOCTOR,
            'auth0Sub' => null,
        ]);

        $this->fingirPerfil(new PerfilAuth0('google-oauth2|777', 'maria@ejemplo.com', true, 'Maria'));

        $this->postJson('/api/auth/auth0', ['accessToken' => 'da-igual'])
            ->assertOk()
            ->assertJsonPath('usuario.id', $existente->id)
            ->assertJsonPath('usuario.rol', User::ROL_DOCTOR);

        $this->assertSame('google-oauth2|777', $existente->fresh()->auth0Sub);
        $this->assertSame(1, User::count());

        // Vincular una identidad externa a una cuenta es al menos tan sensible
        // como reclamar una precalificacion: tiene que quedar en la bitacora.
        $this->assertDatabaseHas('audit_logs', [
            'usuarioId' => $existente->id,
            'entidad' => 'User',
            'entidadId' => $existente->id,
            'accion' => 'vincular',
        ]);
    }

    public function test_una_cuenta_paciente_vieja_sin_fila_de_pacientes_la_gana_al_iniciar_sesion(): void
    {
        // Cuenta creada por el panel (POST /usuarios) antes del hook, o
        // anterior a que el alta creara la fila: rol paciente pero sin fila en
        // pacientes. Sin el fix, toda la API le respondia 422 "El usuario no
        // tiene perfil de paciente".
        $vieja = User::factory()->create([
            'email' => 'veterana@ejemplo.com',
            'rol' => User::ROL_PACIENTE,
            'auth0Sub' => null,
        ]);
        $this->assertDatabaseMissing('pacientes', ['usuarioId' => $vieja->id]);

        $this->fingirPerfil(new PerfilAuth0('auth0|veterana', 'veterana@ejemplo.com', true, 'Veterana'));

        $this->postJson('/api/auth/auth0', ['accessToken' => 'da-igual'])
            ->assertOk()
            ->assertJsonPath('usuario.id', $vieja->id);

        $this->assertDatabaseHas('pacientes', ['usuarioId' => $vieja->id, 'deleted_at' => null]);
    }

    public function test_una_fila_de_paciente_soft_deleted_se_restaura_al_iniciar_sesion(): void
    {
        $usuario = User::factory()->conAuth0('auth0|renacida')->create([
            'email' => 'renacida@ejemplo.com',
            'rol' => User::ROL_PACIENTE,
        ]);
        Paciente::create(['usuarioId' => $usuario->id, 'tipoDiabetes' => 'DM2'])->delete();

        $this->fingirPerfil(new PerfilAuth0('auth0|renacida', 'renacida@ejemplo.com', true, 'Renacida'));

        $this->postJson('/api/auth/auth0', ['accessToken' => 'da-igual'])->assertOk();

        // Se restaura la misma fila (con sus datos clinicos), no se crea otra.
        $this->assertSame(1, Paciente::withTrashed()->where('usuarioId', $usuario->id)->count());
        $this->assertSame('DM2', Paciente::where('usuarioId', $usuario->id)->sole()->tipoDiabetes);
    }

    public function test_normaliza_el_correo_de_auth0_para_vincular_una_cuenta_existente(): void
    {
        $existente = User::factory()->create([
            'email' => 'maria@ejemplo.com',
            'rol' => User::ROL_DOCTOR,
            'auth0Sub' => null,
        ]);

        // Auth0 puede devolver el correo con otra capitalizacion y espacios.
        $this->fingirPerfil(new PerfilAuth0('google-oauth2|888', '  Maria@Ejemplo.com  ', true, 'Maria'));

        $this->postJson('/api/auth/auth0', ['accessToken' => 'da-igual'])
            ->assertOk()
            ->assertJsonPath('usuario.id', $existente->id)
            ->assertJsonPath('usuario.rol', User::ROL_DOCTOR);

        $this->assertSame('google-oauth2|888', $existente->fresh()->auth0Sub);
        $this->assertSame(1, User::count());
    }

    public function test_no_vincula_si_el_email_no_esta_verificado(): void
    {
        User::factory()->create(['email' => 'maria@ejemplo.com', 'auth0Sub' => null]);

        $this->fingirPerfil(new PerfilAuth0('google-oauth2|impostor', 'maria@ejemplo.com', false, 'Impostor'));

        $this->postJson('/api/auth/auth0', ['accessToken' => 'da-igual'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Auth0 no ha verificado este correo.');

        $this->assertNull(User::where('email', 'maria@ejemplo.com')->value('auth0Sub'));
        $this->assertSame(1, User::count());
    }

    public function test_rechaza_una_cuenta_dada_de_baja_con_403(): void
    {
        $borrado = User::factory()->create(['email' => 'baja@ejemplo.com', 'auth0Sub' => null]);
        $borrado->delete();

        $this->fingirPerfil(new PerfilAuth0('auth0|reintento', 'baja@ejemplo.com', true, 'Quien sea'));

        $this->postJson('/api/auth/auth0', ['accessToken' => 'da-igual'])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Esta cuenta esta dada de baja. Contacta con soporte.');

        // No debe resucitar la cuenta borrada ni crear una nueva.
        $this->assertSame(1, User::withTrashed()->count());
    }

    public function test_rechaza_un_token_invalido_con_401(): void
    {
        $this->app->instance(VerificadorAuth0::class, new class implements VerificadorAuth0
        {
            public function verificar(string $accessToken): PerfilAuth0
            {
                throw new TokenAuth0Invalido('firma invalida');
            }
        });

        $this->postJson('/api/auth/auth0', ['accessToken' => 'basura'])->assertUnauthorized();

        $this->assertSame(0, User::count());
    }

    public function test_responde_503_si_el_tenant_no_esta_disponible(): void
    {
        $this->app->instance(VerificadorAuth0::class, new class implements VerificadorAuth0
        {
            public function verificar(string $accessToken): PerfilAuth0
            {
                throw new Auth0NoDisponible('tenant caido');
            }
        });

        $this->postJson('/api/auth/auth0', ['accessToken' => 'da-igual'])
            ->assertStatus(503);

        $this->assertSame(0, User::count());
    }

    public function test_exige_el_access_token(): void
    {
        $this->postJson('/api/auth/auth0', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('accessToken');
    }

    public function test_rechaza_un_perfil_sin_correo(): void
    {
        $this->fingirPerfil(new PerfilAuth0('auth0|sin-correo', null, false, null));

        $this->postJson('/api/auth/auth0', ['accessToken' => 'da-igual'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Auth0 no entrego un correo para esta cuenta.');
    }

    public function test_el_token_emitido_sirve_para_consumir_la_api(): void
    {
        $this->fingirPerfil(new PerfilAuth0('auth0|nuevo', 'nueva@ejemplo.com', true, 'Ana'));

        $token = $this->postJson('/api/auth/auth0', ['accessToken' => 'da-igual'])->json('token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('email', 'nueva@ejemplo.com');
    }

    public function test_repetir_el_intercambio_en_el_mismo_dispositivo_no_acumula_tokens(): void
    {
        $this->fingirPerfil(new PerfilAuth0('auth0|nuevo', 'nueva@ejemplo.com', true, 'Ana'));

        foreach (range(1, 3) as $ignorado) {
            $this->postJson('/api/auth/auth0', [
                'accessToken' => 'da-igual',
                'dispositivo' => 'android-1',
            ])->assertOk();
        }

        $usuario = User::where('email', 'nueva@ejemplo.com')->sole();

        $this->assertSame(1, $usuario->tokens()->where('name', 'android-1')->count());
    }

    public function test_el_logout_revoca_el_token_actual(): void
    {
        $usuario = User::factory()->conAuth0('auth0|abc')->create();
        $token = $usuario->createToken('android-1')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout')
            ->assertNoContent();

        $this->assertSame(0, $usuario->tokens()->count());
    }

    public function test_las_rutas_de_autenticacion_local_ya_no_existen(): void
    {
        foreach (['/api/auth/login', '/api/auth/register', '/api/auth/forgot-password'] as $ruta) {
            $this->postJson($ruta, [])->assertNotFound();
        }
    }

    private function fingirPerfil(PerfilAuth0 $perfil): void
    {
        $this->app->instance(VerificadorAuth0::class, new class($perfil) implements VerificadorAuth0
        {
            public function __construct(private PerfilAuth0 $perfil) {}

            public function verificar(string $accessToken): PerfilAuth0
            {
                return $this->perfil;
            }
        });
    }
}
