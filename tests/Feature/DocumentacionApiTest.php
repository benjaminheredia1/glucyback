<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * La documentacion OpenAPI se genera desde los atributos #[OA\...] de los
 * controladores y de los archivos de app/OpenApi. Este test asegura que el
 * conjunto compila y que cubre todas las rutas de la API.
 */
class DocumentacionApiTest extends TestCase
{
    public function test_genera_openapi_y_cubre_todas_las_rutas_de_la_api(): void
    {
        $this->artisan('l5-swagger:generate')->assertSuccessful();

        $ruta = storage_path('api-docs/api-docs.json');
        $this->assertFileExists($ruta);

        $doc = json_decode(File::get($ruta), true);

        $this->assertSame('3.0.0', $doc['openapi']);
        $this->assertArrayHasKey('bearerAuth', $doc['components']['securitySchemes']);

        $documentadas = [];
        foreach ($doc['paths'] as $uri => $operaciones) {
            foreach (array_keys($operaciones) as $metodo) {
                $documentadas[] = strtoupper($metodo).' '.$uri;
            }
        }

        $faltantes = [];
        foreach (app('router')->getRoutes() as $ruta) {
            // Las rutas de la propia UI de Swagger (api/documentation, ...) no son API.
            if (! str_starts_with($ruta->uri(), 'api/') || str_starts_with((string) $ruta->getName(), 'l5-swagger.')) {
                continue;
            }
            $uri = '/'.substr($ruta->uri(), 4);
            foreach ($ruta->methods() as $metodo) {
                if ($metodo === 'HEAD') {
                    continue;
                }
                if (! in_array("$metodo $uri", $documentadas, true)) {
                    $faltantes[] = "$metodo $uri";
                }
            }
        }

        $this->assertSame([], $faltantes, 'Rutas de la API sin documentar: '.implode(', ', $faltantes));
    }
}
