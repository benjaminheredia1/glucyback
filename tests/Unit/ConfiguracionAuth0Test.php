<?php

namespace Tests\Unit;

use Tests\TestCase;

class ConfiguracionAuth0Test extends TestCase
{
    public function test_las_claves_de_auth0_existen_aunque_el_entorno_este_vacio(): void
    {
        // config() devolveria null igualmente para una clave inexistente, asi que
        // se comprueba que el bloque este declarado y no solo que no reviente.
        $this->assertArrayHasKey('auth0', config('services'));
        $this->assertArrayHasKey('domain', config('services.auth0'));
        $this->assertArrayHasKey('audience', config('services.auth0'));
    }

    public function test_cors_solo_cubre_la_api(): void
    {
        $this->assertSame(['api/*'], config('cors.paths'));
    }
}
