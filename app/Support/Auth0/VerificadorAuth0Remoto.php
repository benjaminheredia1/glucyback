<?php

namespace App\Support\Auth0;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;
use UnexpectedValueException;

/**
 * Verifica el access token contra las JWKS del tenant y completa el perfil con
 * /userinfo.
 *
 * El access token emitido para una API propia lleva solo `sub`, `aud`, `iss`,
 * `exp` y `scope`: el correo hay que pedirlo aparte. Por eso la app tiene que
 * solicitar los scopes `openid profile email` en Universal Login.
 */
class VerificadorAuth0Remoto implements VerificadorAuth0
{
    private const PREFIJO_CACHE_JWKS = 'auth0:jwks:';

    private const TTL_JWKS = 3600;

    // Mensaje exacto que firebase/php-jwt lanza cuando el kid del token no esta
    // en el set de claves (JWT::getKey(), vendor/firebase/php-jwt/src/JWT.php).
    // Se compara literal para no refrescar JWKS ante cualquier otro fallo.
    private const MENSAJE_KID_DESCONOCIDO = '"kid" invalid, unable to lookup correct key';

    public function verificar(string $accessToken): PerfilAuth0
    {
        $dominio = config('services.auth0.domain');
        $audiencia = config('services.auth0.audience');

        if (blank($dominio) || blank($audiencia)) {
            throw new Auth0NoDisponible('El tenant de Auth0 no esta configurado.');
        }

        $reclamos = $this->decodificar($accessToken, $dominio);

        $this->comprobarEmisor($reclamos, $dominio);
        $this->comprobarAudiencia($reclamos, $audiencia);

        return $this->perfil($accessToken, $dominio, $reclamos['sub']);
    }

    /** @return array<string,mixed> */
    private function decodificar(string $accessToken, string $dominio): array
    {
        // Resolver las claves queda fuera del try de abajo a proposito: si las
        // JWKS no se pueden obtener o interpretar (transporte o payload), es un
        // problema nuestro (Auth0NoDisponible), no del token que llego.
        $claves = $this->clavesPublicas($dominio);

        try {
            // JWT::decode valida firma, exp, iat y nbf. El emisor y la audiencia
            // no los mira: se comprueban a mano justo despues.
            $reclamos = (array) JWT::decode($accessToken, $claves);
        } catch (UnexpectedValueException $e) {
            $reclamos = $this->reintentarTrasRotacion($e, $accessToken, $dominio);
        } catch (Throwable $e) {
            throw new TokenAuth0Invalido('Access token invalido: '.$e->getMessage(), previous: $e);
        }

        $sub = $reclamos['sub'] ?? null;

        if (! is_string($sub) || $sub === '') {
            throw new TokenAuth0Invalido('El token no trae un sub valido.');
        }

        return $reclamos;
    }

    /**
     * Auth0 rota su clave de firma periodicamente (operacion normal). Si el kid
     * del token no esta en las JWKS cacheadas, puede ser justo eso y no un
     * token invalido: se invalida la cache, se piden las JWKS de nuevo una
     * sola vez y se reintenta. Si el kid tampoco aparece en el set fresco, ahi
     * si es un token invalido de verdad.
     *
     * @return array<string,mixed>
     */
    private function reintentarTrasRotacion(UnexpectedValueException $original, string $accessToken, string $dominio): array
    {
        if ($original->getMessage() !== self::MENSAJE_KID_DESCONOCIDO) {
            throw new TokenAuth0Invalido('Access token invalido: '.$original->getMessage(), previous: $original);
        }

        Cache::forget($this->claveCache($dominio));

        $clavesFrescas = $this->clavesPublicas($dominio);

        try {
            return (array) JWT::decode($accessToken, $clavesFrescas);
        } catch (Throwable $e) {
            // Sin refetch de por medio esta vez: un solo reintento, no un bucle.
            throw new TokenAuth0Invalido('Access token invalido: '.$e->getMessage(), previous: $e);
        }
    }

    /** @return array<string,\Firebase\JWT\Key> */
    private function clavesPublicas(string $dominio): array
    {
        try {
            return JWK::parseKeySet($this->jwks($dominio));
        } catch (Auth0NoDisponible $e) {
            throw $e;
        } catch (Throwable $e) {
            // Un 200 con {"keys": []} o sin la clave "keys" no es un token malo:
            // es que el tenant nos esta sirviendo un JWKS que no podemos usar.
            throw new Auth0NoDisponible('Las JWKS del tenant no se pudieron interpretar: '.$e->getMessage(), previous: $e);
        }
    }

    /** @param array<string,mixed> $reclamos */
    private function comprobarEmisor(array $reclamos, string $dominio): void
    {
        if (($reclamos['iss'] ?? null) !== "https://{$dominio}/") {
            throw new TokenAuth0Invalido('El token no lo emitio este tenant.');
        }
    }

    /** @param array<string,mixed> $reclamos */
    private function comprobarAudiencia(array $reclamos, string $audiencia): void
    {
        // Auth0 manda `aud` como cadena o como lista, segun los scopes pedidos.
        $aud = (array) ($reclamos['aud'] ?? []);

        if (! in_array($audiencia, $aud, true)) {
            throw new TokenAuth0Invalido('El token no es para esta API.');
        }
    }

    private function perfil(string $accessToken, string $dominio, string $sub): PerfilAuth0
    {
        try {
            $respuesta = Http::withToken($accessToken)
                ->timeout(10)
                ->acceptJson()
                ->get("https://{$dominio}/userinfo");
        } catch (Throwable $e) {
            throw new Auth0NoDisponible('No se pudo consultar /userinfo.', previous: $e);
        }

        if ($respuesta->failed()) {
            throw new Auth0NoDisponible('/userinfo respondio '.$respuesta->status());
        }

        $datos = $respuesta->json();

        // La identidad la fija la firma del access token, no el JSON de
        // /userinfo: el sub ya verificado criptograficamente siempre gana,
        // /userinfo solo aporta los datos que el token no trae.
        return new PerfilAuth0(
            sub: $sub,
            email: $datos['email'] ?? null,
            // Identidad estricta, no un cast (bool): en PHP (bool) "false" es
            // true porque cualquier cadena no vacia es verdadera. Si Auth0
            // alguna vez manda email_verified como la cadena literal "false"
            // (documentado para algunas cuentas via Management API o conexion
            // de base de datos propia), el cast lo invertiria y esta cuenta se
            // trataria como verificada sin estarlo.
            emailVerificado: ($datos['email_verified'] ?? null) === true,
            nombre: $datos['name'] ?? null,
        );
    }

    /** @return array<string,mixed> */
    private function jwks(string $dominio): array
    {
        return Cache::remember($this->claveCache($dominio), self::TTL_JWKS, function () use ($dominio) {
            // Igual que en perfil(): un fallo de conexion (dns, timeout, rechazo)
            // no llega como respuesta fallida sino como excepcion, y hay que
            // convertirlo tambien en Auth0NoDisponible en vez de dejarlo escapar
            // como si fuera un problema del token.
            try {
                $respuesta = Http::timeout(10)
                    ->acceptJson()
                    ->get("https://{$dominio}/.well-known/jwks.json");
            } catch (Throwable $e) {
                throw new Auth0NoDisponible('No se pudieron leer las JWKS del tenant.', previous: $e);
            }

            if ($respuesta->failed()) {
                throw new Auth0NoDisponible('No se pudieron leer las JWKS del tenant.');
            }

            return $respuesta->json();
        });
    }

    // Por dominio, no una clave fija: un cache store compartido entre tenants
    // o entornos (p. ej. staging y produccion) no debe mezclar las JWKS de uno
    // con las del otro.
    private function claveCache(string $dominio): string
    {
        return self::PREFIJO_CACHE_JWKS.$dominio;
    }
}
