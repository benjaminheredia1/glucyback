<?php

namespace Tests\Unit;

use App\Support\Auth0\Auth0NoDisponible;
use App\Support\Auth0\TokenAuth0Invalido;
use App\Support\Auth0\VerificadorAuth0Remoto;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use OpenSSLAsymmetricKey;
use Tests\TestCase;

class VerificadorAuth0RemotoTest extends TestCase
{
    private const DOMINIO = 'glucy.us.auth0.com';
    private const AUDIENCIA = 'https://api.glucy.local';
    private const KID = 'clave-de-prueba';

    private OpenSSLAsymmetricKey $clavePrivada;

    private array $jwks;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.auth0.domain' => self::DOMINIO,
            'services.auth0.audience' => self::AUDIENCIA,
        ]);

        // openssl_pkey_new() genera la clave al vuelo, pero eso exige que PHP
        // encuentre openssl.cnf; en varias instalaciones de Windows (Laragon
        // incluida) no lo encuentra y devuelve false. Leer una clave ya
        // generada no depende de ese archivo, asi que se fija como fixture.
        // Vease tests/Fixtures/README.md.
        $clave = openssl_pkey_get_private(
            file_get_contents(__DIR__.'/../Fixtures/auth0-firma-de-prueba.pem')
        );

        if ($clave === false) {
            $this->fail('No se pudo leer tests/Fixtures/auth0-firma-de-prueba.pem.');
        }

        $this->clavePrivada = $clave;

        $detalles = openssl_pkey_get_details($this->clavePrivada);

        $this->jwks = ['keys' => [[
            'kty' => 'RSA',
            'kid' => self::KID,
            'use' => 'sig',
            'alg' => 'RS256',
            'n' => $this->base64Url($detalles['rsa']['n']),
            'e' => $this->base64Url($detalles['rsa']['e']),
        ]]];
    }

    public function test_devuelve_el_perfil_con_un_token_valido(): void
    {
        $this->fingirAuth0(perfil: [
            'sub' => 'auth0|abc123',
            'email' => 'maria@ejemplo.com',
            'email_verified' => true,
            'name' => 'Maria Torres',
        ]);

        $perfil = $this->verificador()->verificar($this->token());

        $this->assertSame('auth0|abc123', $perfil->sub);
        $this->assertSame('maria@ejemplo.com', $perfil->email);
        $this->assertTrue($perfil->emailVerificado);
        $this->assertSame('Maria Torres', $perfil->nombre);
    }

    public function test_email_verified_como_la_cadena_false_no_se_toma_como_verificado(): void
    {
        // (bool) "false" es true en PHP: cualquier cadena no vacia es
        // verdadera. Si el cast (bool) se coló de vuelta en vez del === true
        // estricto, este test lo atrapa: Auth0 documenta email_verified como
        // cadena "false" para algunas cuentas via Management API o conexion
        // de base de datos propia.
        $this->fingirAuth0(perfil: [
            'sub' => 'auth0|abc123',
            'email' => 'maria@ejemplo.com',
            'email_verified' => 'false',
            'name' => 'Maria Torres',
        ]);

        $perfil = $this->verificador()->verificar($this->token());

        $this->assertFalse($perfil->emailVerificado);
    }

    public function test_el_sub_del_perfil_es_el_del_token_y_no_el_de_userinfo(): void
    {
        // El sub del token esta verificado criptograficamente; el de /userinfo
        // es solo JSON. Si /userinfo trajera un sub distinto (bug, tenant mal
        // configurado, lo que sea), el verificado tiene que ganar siempre.
        $this->fingirAuth0(perfil: [
            'sub' => 'auth0|otro-distinto',
            'email' => 'maria@ejemplo.com',
            'email_verified' => true,
            'name' => 'Maria Torres',
        ]);

        $perfil = $this->verificador()->verificar($this->token());

        $this->assertSame('auth0|abc123', $perfil->sub);
    }

    public function test_rechaza_un_token_firmado_con_otra_clave(): void
    {
        $this->fingirAuth0();

        $intrusa = openssl_pkey_get_private(
            file_get_contents(__DIR__.'/../Fixtures/auth0-clave-intrusa-de-prueba.pem')
        );

        if ($intrusa === false) {
            $this->fail('No se pudo leer tests/Fixtures/auth0-clave-intrusa-de-prueba.pem.');
        }

        $this->expectException(TokenAuth0Invalido::class);

        $this->verificador()->verificar($this->token(clave: $intrusa));
    }

    public function test_rechaza_un_token_para_otra_audiencia(): void
    {
        $this->fingirAuth0();

        $this->expectException(TokenAuth0Invalido::class);

        $this->verificador()->verificar($this->token(reclamos: ['aud' => 'https://api.de-otro.com']));
    }

    public function test_rechaza_un_token_de_otro_emisor(): void
    {
        $this->fingirAuth0();

        $this->expectException(TokenAuth0Invalido::class);

        $this->verificador()->verificar($this->token(reclamos: ['iss' => 'https://malicioso.example/']));
    }

    public function test_rechaza_un_token_cuyo_sub_no_es_una_cadena(): void
    {
        $this->fingirAuth0();

        // Un sub que no es cadena (array u objeto) tiene que rechazarse aqui,
        // como token invalido: si se dejara pasar, perfil(string $sub) lo
        // recibiria con el tipo equivocado y produciria un TypeError sin
        // capturar (500) en vez de un fallo limpio.
        $this->expectException(TokenAuth0Invalido::class);

        $this->verificador()->verificar($this->token(reclamos: ['sub' => ['auth0|abc123']]));
    }

    public function test_rechaza_un_token_caducado(): void
    {
        $this->fingirAuth0();

        $this->expectException(TokenAuth0Invalido::class);

        $this->verificador()->verificar($this->token(reclamos: [
            'iat' => time() - 7200,
            'exp' => time() - 3600,
        ]));
    }

    public function test_acepta_la_audiencia_cuando_aud_es_una_lista(): void
    {
        $this->fingirAuth0();

        $perfil = $this->verificador()->verificar($this->token(reclamos: [
            'aud' => [self::AUDIENCIA, 'https://'.self::DOMINIO.'/userinfo'],
        ]));

        $this->assertSame('auth0|abc123', $perfil->sub);
    }

    public function test_falla_como_no_disponible_si_userinfo_responde_mal(): void
    {
        Http::fake([
            'https://'.self::DOMINIO.'/.well-known/jwks.json' => Http::response($this->jwks),
            'https://'.self::DOMINIO.'/userinfo' => Http::response(null, 500),
        ]);

        $this->expectException(Auth0NoDisponible::class);

        $this->verificador()->verificar($this->token());
    }

    public function test_falla_como_no_disponible_si_las_jwks_no_traen_claves(): void
    {
        // Un 200 con {"keys": []} (o sin "keys") es un JWKS inservible: culpa
        // del tenant, no del token que llego. No debe confundirse con un token
        // invalido.
        Http::fake([
            'https://'.self::DOMINIO.'/.well-known/jwks.json' => Http::response(['keys' => []]),
        ]);

        $this->expectException(Auth0NoDisponible::class);

        $this->verificador()->verificar($this->token());
    }

    public function test_falla_como_no_disponible_si_el_tenant_no_esta_configurado(): void
    {
        config(['services.auth0.domain' => null]);

        Http::fake();

        $this->expectException(Auth0NoDisponible::class);

        try {
            $this->verificador()->verificar('lo-que-sea');
        } finally {
            // La diferencia real que prueba este test es que el guard corta
            // antes de tocar la red: sin el, {"keys": []} tambien acabaria en
            // Auth0NoDisponible (ver test de arriba), pero habria pedido JWKS.
            Http::assertNothingSent();
        }
    }

    public function test_falla_como_no_disponible_si_la_audiencia_no_esta_configurada(): void
    {
        config(['services.auth0.audience' => null]);

        Http::fake();

        $this->expectException(Auth0NoDisponible::class);

        try {
            $this->verificador()->verificar('lo-que-sea');
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_las_jwks_se_piden_una_sola_vez(): void
    {
        $this->fingirAuth0();

        $verificador = $this->verificador();
        $verificador->verificar($this->token());
        $verificador->verificar($this->token());

        Http::assertSentCount(3); // 1 JWKS cacheadas + 2 llamadas a /userinfo
    }

    public function test_un_kid_desconocido_refresca_las_jwks_una_vez_y_reintenta(): void
    {
        // Simula una rotacion de clave en Auth0: el token trae un kid que las
        // JWKS cacheadas todavia no conocen, pero el refetch si lo trae.
        $kidRotado = 'clave-rotada';
        $token = $this->token(kid: $kidRotado);

        Http::fake([
            'https://'.self::DOMINIO.'/.well-known/jwks.json' => Http::sequence()
                ->push($this->jwks) // primera lectura: no trae la clave nueva
                ->push($this->jwksConKid($kidRotado)), // tras el refetch, si
            'https://'.self::DOMINIO.'/userinfo' => Http::response([
                'sub' => 'auth0|abc123',
                'email' => 'maria@ejemplo.com',
                'email_verified' => true,
                'name' => 'Maria Torres',
            ]),
        ]);

        $perfil = $this->verificador()->verificar($token);

        $this->assertSame('auth0|abc123', $perfil->sub);
        Http::assertSentCount(3); // 2 JWKS (original + refetch) + 1 userinfo
    }

    public function test_un_kid_desconocido_en_las_jwks_frescas_tambien_falla_sin_reintentar_de_nuevo(): void
    {
        $kidRotado = 'clave-rotada';
        $token = $this->token(kid: $kidRotado);

        Http::fake([
            // El refetch tampoco trae la clave nueva: el token si es invalido.
            'https://'.self::DOMINIO.'/.well-known/jwks.json' => Http::sequence()
                ->push($this->jwks)
                ->push($this->jwks),
        ]);

        $this->expectException(TokenAuth0Invalido::class);

        try {
            $this->verificador()->verificar($token);
        } finally {
            // Exactamente un refetch, no un bucle: dos peticiones de JWKS y
            // ninguna a /userinfo (nunca se llega a decodificar el token).
            Http::assertSentCount(2);
        }
    }

    // ------------------------------------------------------------- utilidades

    private function verificador(): VerificadorAuth0Remoto
    {
        return $this->app->make(VerificadorAuth0Remoto::class);
    }

    private function fingirAuth0(array $perfil = []): void
    {
        $perfil = $perfil ?: [
            'sub' => 'auth0|abc123',
            'email' => 'maria@ejemplo.com',
            'email_verified' => true,
            'name' => 'Maria Torres',
        ];

        Http::fake([
            'https://'.self::DOMINIO.'/.well-known/jwks.json' => Http::response($this->jwks),
            'https://'.self::DOMINIO.'/userinfo' => Http::response($perfil),
        ]);
    }

    private function token(array $reclamos = [], ?OpenSSLAsymmetricKey $clave = null, ?string $kid = null): string
    {
        return JWT::encode([
            'iss' => 'https://'.self::DOMINIO.'/',
            'sub' => 'auth0|abc123',
            'aud' => self::AUDIENCIA,
            'iat' => time(),
            'exp' => time() + 3600,
            ...$reclamos,
        ], $clave ?? $this->clavePrivada, 'RS256', $kid ?? self::KID);
    }

    /** Mismo par de claves que $this->jwks, pero publicado bajo otro kid. */
    private function jwksConKid(string $kid): array
    {
        $detalles = openssl_pkey_get_details($this->clavePrivada);

        return ['keys' => [[
            'kty' => 'RSA',
            'kid' => $kid,
            'use' => 'sig',
            'alg' => 'RS256',
            'n' => $this->base64Url($detalles['rsa']['n']),
            'e' => $this->base64Url($detalles['rsa']['e']),
        ]]];
    }

    private function base64Url(string $binario): string
    {
        return rtrim(strtr(base64_encode($binario), '+/', '-_'), '=');
    }
}
