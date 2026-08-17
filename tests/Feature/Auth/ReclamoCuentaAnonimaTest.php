<?php

namespace Tests\Feature\Auth;

use App\Models\Paciente;
use App\Models\TipoEstudio;
use App\Models\User;
use App\Support\Auth0\PerfilAuth0;
use App\Support\Auth0\VerificadorAuth0;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReclamoCuentaAnonimaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_anonimo_reclama_su_cuenta_sobre_la_misma_fila(): void
    {
        [$token, $anonimo] = $this->altaAnonima();
        $pacienteId = Paciente::where('usuarioId', $anonimo->id)->sole()->id;

        $tipo = TipoEstudio::create(['nombre' => 'HbA1c']);
        $estudioId = $this->conToken($token)->postJson('/api/estudios-medicos', [
            'tipoEstudioId' => $tipo->id,
            'fecha' => now()->toDateString(),
        ])->assertCreated()->json('id');

        $this->fingirPerfil(new PerfilAuth0('auth0|ana', 'Ana@Ejemplo.com', true, 'Ana Ibarra'));

        $respuesta = $this->conToken($token)->postJson('/api/auth/auth0', ['accessToken' => 'da-igual']);

        $respuesta->assertOk()
            ->assertJsonPath('usuario.id', $anonimo->id)
            ->assertJsonPath('usuario.email', 'ana@ejemplo.com')
            ->assertJsonPath('usuario.name', 'Ana Ibarra')
            ->assertJsonPath('usuario.esTemporal', false);

        $this->assertDatabaseHas('users', ['id' => $anonimo->id, 'email' => 'ana@ejemplo.com', 'auth0Sub' => 'auth0|ana']);
        $this->assertNotNull($anonimo->fresh()->email_verified_at);
        // Misma fila: no nacio un segundo usuario ni un segundo paciente.
        $this->assertSame(1, User::count());
        $this->assertSame(1, Paciente::count());

        // El estudio subido como anonimo sigue siendo suyo con el token nuevo.
        $this->conToken($respuesta->json('token'))
            ->getJson("/api/estudios-medicos/{$estudioId}")
            ->assertOk()
            ->assertJsonPath('pacienteId', $pacienteId);

        // El reclamo cierra la sesion anonima.
        $this->conToken($token)->getJson('/api/user')->assertUnauthorized();

        $this->assertDatabaseHas('audit_logs', [
            'usuarioId' => $anonimo->id,
            'entidad' => 'User',
            'entidadId' => $anonimo->id,
            'accion' => 'reclamar',
        ]);
    }

    public function test_el_nombre_editado_antes_del_reclamo_se_conserva(): void
    {
        [$token] = $this->altaAnonima();

        $this->conToken($token)->patchJson('/api/perfil', ['name' => 'Ana'])->assertOk();

        $this->fingirPerfil(new PerfilAuth0('auth0|ana', 'ana@ejemplo.com', true, 'Nombre De Google'));

        $this->conToken($token)->postJson('/api/auth/auth0', ['accessToken' => 'da-igual'])
            ->assertOk()
            ->assertJsonPath('usuario.name', 'Ana');
    }

    public function test_si_el_correo_ya_tiene_cuenta_responde_409_y_el_anonimo_queda_intacto(): void
    {
        User::factory()->create(['email' => 'ana@ejemplo.com']);
        [$token, $anonimo] = $this->altaAnonima();

        $this->fingirPerfil(new PerfilAuth0('auth0|ana', 'ana@ejemplo.com', true, 'Ana'));

        $this->conToken($token)->postJson('/api/auth/auth0', ['accessToken' => 'da-igual'])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Ya existe una cuenta con este correo. Inicia sesion con ella.');

        $this->assertNull($anonimo->fresh()->email);
        $this->assertSame(2, User::count());
        // El anonimo sigue vivo: su token todavia sirve.
        $this->conToken($token)->getJson('/api/user')->assertOk();
    }

    public function test_si_el_sub_ya_tiene_cuenta_responde_409(): void
    {
        User::factory()->conAuth0('auth0|ana')->create(['email' => 'vieja@ejemplo.com']);
        [$token, $anonimo] = $this->altaAnonima();

        $this->fingirPerfil(new PerfilAuth0('auth0|ana', 'nueva@ejemplo.com', true, 'Ana'));

        $this->conToken($token)->postJson('/api/auth/auth0', ['accessToken' => 'da-igual'])->assertStatus(409);

        $this->assertNull($anonimo->fresh()->email);
    }

    // Va antes que el 409: un correo sin verificar no debe poder sondear si
    // existe una cuenta.
    public function test_un_correo_sin_verificar_no_reclama(): void
    {
        User::factory()->create(['email' => 'ana@ejemplo.com']);
        [$token, $anonimo] = $this->altaAnonima();

        $this->fingirPerfil(new PerfilAuth0('auth0|ana', 'ana@ejemplo.com', false, 'Ana'));

        $this->conToken($token)->postJson('/api/auth/auth0', ['accessToken' => 'da-igual'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Auth0 no ha verificado este correo para reclamar la cuenta.');

        $this->assertNull($anonimo->fresh()->email);
    }

    public function test_una_cuenta_dada_de_baja_no_se_reclama(): void
    {
        $borrado = User::factory()->create(['email' => 'ana@ejemplo.com']);
        $borrado->delete();
        [$token, $anonimo] = $this->altaAnonima();

        $this->fingirPerfil(new PerfilAuth0('auth0|ana', 'ana@ejemplo.com', true, 'Ana'));

        $this->conToken($token)->postJson('/api/auth/auth0', ['accessToken' => 'da-igual'])->assertForbidden();

        $this->assertNull($anonimo->fresh()->email);
    }

    public function test_un_bearer_invalido_responde_401_y_no_crea_nada(): void
    {
        $this->fingirPerfil(new PerfilAuth0('auth0|ana', 'ana@ejemplo.com', true, 'Ana'));

        $this->conToken('1|token-inexistente')
            ->postJson('/api/auth/auth0', ['accessToken' => 'da-igual'])
            ->assertUnauthorized();

        $this->assertSame(0, User::count());
    }

    public function test_el_bearer_de_una_cuenta_real_no_reclama_sino_que_reingresa(): void
    {
        $usuario = User::factory()->conAuth0('auth0|ana')->create(['email' => 'ana@ejemplo.com', 'rol' => User::ROL_PACIENTE]);
        Paciente::create(['usuarioId' => $usuario->id]);
        $token = $usuario->createToken('api')->plainTextToken;

        $this->fingirPerfil(new PerfilAuth0('auth0|ana', 'ana@ejemplo.com', true, 'Ana'));

        $this->conToken($token)->postJson('/api/auth/auth0', ['accessToken' => 'da-igual'])
            ->assertOk()
            ->assertJsonPath('usuario.id', $usuario->id);

        $this->assertSame(1, User::count());
        $this->assertDatabaseMissing('audit_logs', ['accion' => 'reclamar']);
    }

    public function test_sin_bearer_el_alta_por_auth0_sigue_creando_paciente_nuevo(): void
    {
        $this->fingirPerfil(new PerfilAuth0('auth0|nuevo', 'nueva@ejemplo.com', true, 'Ana Ibarra'));

        $this->postJson('/api/auth/auth0', ['accessToken' => 'da-igual'])
            ->assertOk()
            ->assertJsonPath('usuario.email', 'nueva@ejemplo.com')
            ->assertJsonPath('usuario.esTemporal', false);

        $this->assertDatabaseHas('audit_logs', ['accion' => 'crear', 'entidad' => 'User']);
    }

    /**
     * Manda el Bearer y olvida el usuario que el guard cacheo en la peticion
     * anterior del mismo test (RequestGuard::$user). Sin esto, un token ya
     * revocado "seguiria sirviendo" solo en memoria del test.
     */
    private function conToken(string $token): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }

    /**
     * @return array{0: string, 1: User} token anonimo y su usuario
     */
    private function altaAnonima(): array
    {
        $respuesta = $this->postJson('/api/auth/anonimo')->assertCreated();

        return [$respuesta->json('token'), User::findOrFail($respuesta->json('usuario.id'))];
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
