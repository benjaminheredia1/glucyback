<?php

namespace App\Console\Commands;

use App\Http\Controllers\BaseCrudController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Support\Str;
use ReflectionClass;

/**
 * Genera app/OpenApi/Crud/*.php: un archivo por entidad con los atributos
 * OpenAPI de sus operaciones CRUD (listar/crear/ver/actualizar/eliminar).
 *
 * Las operaciones salen de las rutas registradas y los campos del cuerpo de
 * `reglas()` de cada BaseCrudController, asi la doc no se desfasa del
 * validador. Las acciones propias (atender, firmar, ...) se anotan a mano en
 * el controlador. Correr de nuevo tras cambiar reglas o rutas CRUD.
 */
class GenerarOpenApiCrud extends Command
{
    protected $signature = 'openapi:crud';

    protected $description = 'Genera los atributos OpenAPI de las operaciones CRUD en app/OpenApi/Crud';

    private const ACCIONES = ['listar', 'crear', 'ver', 'actualizar', 'eliminar'];

    /** Parametros extra de listado que no salen de `$filtrables`. */
    private const QUERY_EXTRA = [
        'tomas' => [
            ['dia', 'string', 'Dia local (YYYY-MM-DD) del que se quieren las tomas', 'date'],
            ['zona', 'string', 'Zona horaria IANA del paciente (ej. America/La_Paz)', null],
        ],
    ];

    public function handle(): int
    {
        $directorio = app_path('OpenApi/Crud');
        File::ensureDirectoryExists($directorio);
        File::cleanDirectory($directorio);

        $porControlador = [];

        foreach (Router::getRoutes() as $ruta) {
            $controlador = $ruta->getControllerClass();

            if ($controlador === null || ! is_subclass_of($controlador, BaseCrudController::class)) {
                continue;
            }

            $accion = $ruta->getActionMethod();

            if (! in_array($accion, self::ACCIONES, true)) {
                continue;
            }

            foreach ($ruta->methods() as $metodo) {
                if ($metodo === 'HEAD') {
                    continue;
                }
                $porControlador[$controlador][] = [$metodo, $ruta, $accion];
            }
        }

        ksort($porControlador);

        foreach ($porControlador as $controlador => $operaciones) {
            $archivo = $directorio.'/'.Str::replaceLast('Controller', '', class_basename($controlador)).'Docs.php';
            File::put($archivo, $this->generar($controlador, $operaciones));
            $this->line('  '.basename($archivo));
        }

        $this->info(count($porControlador).' entidades documentadas.');

        return self::SUCCESS;
    }

    /**
     * @param  array<array{0:string,1:Route,2:string}>  $operaciones
     */
    private function generar(string $controlador, array $operaciones): string
    {
        $instancia = app($controlador);
        $reflexion = new ReflectionClass($instancia);

        $entidad = Str::replaceLast('Controller', '', class_basename($controlador));
        $prefijo = $this->prefijo($operaciones[0][1]);
        $tag = Str::headline(Str::replaceLast('Docs', '', $entidad));

        $propiedades = fn (string $nombre) => $reflexion->getProperty($nombre)->getValue($instancia);
        $filtrables = $propiedades('filtrables');
        $ordenables = $propiedades('ordenables');
        $rolesLectura = $propiedades('rolesLectura');
        $rolesEscritura = $propiedades('rolesEscritura');

        $reglasCrear = $this->reglas($reflexion, $instancia, true);
        $reglasActualizar = $this->reglas($reflexion, $instancia, false);

        $clase = $entidad.'Docs';
        $schemaCrear = $entidad.'Crear';
        $schemaActualizar = $entidad.'Actualizar';
        $schemaRecurso = $entidad;

        $salida = [];
        $salida[] = '<?php';
        $salida[] = '';
        $salida[] = '// Generado por `php artisan openapi:crud`. No editar a mano.';
        $salida[] = '';
        $salida[] = 'namespace App\OpenApi\Crud;';
        $salida[] = '';
        $salida[] = 'use OpenApi\Attributes as OA;';
        $salida[] = '';
        $salida[] = "#[OA\\Tag(name: '{$tag}', description: 'Lectura: ".implode(', ', $rolesLectura).'. Escritura: '.implode(', ', $rolesEscritura).".')]";
        $salida[] = $this->schema($schemaRecurso, $reglasCrear, conId: true, requeridos: false);
        $salida[] = $this->schema($schemaCrear, $reglasCrear, conId: false, requeridos: true);
        $salida[] = $this->schema($schemaActualizar, $reglasActualizar, conId: false, requeridos: false);
        $salida[] = "final class {$clase}";
        $salida[] = '{';

        $metodosEmitidos = [];

        foreach ($operaciones as [$metodoHttp, $ruta, $accion]) {
            $nombreMetodo = strtolower($metodoHttp).Str::studly($accion);
            if (isset($metodosEmitidos[$nombreMetodo])) {
                continue;
            }
            $metodosEmitidos[$nombreMetodo] = true;

            $path = '/'.Str::after($ruta->uri(), 'api/');
            $salida[] = $this->operacion($metodoHttp, $path, $accion, $tag, $entidad, $filtrables, $ordenables, $reglasCrear, $schemaRecurso, $schemaCrear, $schemaActualizar, $prefijo);
            $salida[] = "    public function {$nombreMetodo}(): void {}";
            $salida[] = '';
        }

        array_pop($salida);
        $salida[] = '}';
        $salida[] = '';

        return implode("\n", $salida);
    }

    private function prefijo(Route $ruta): string
    {
        return Str::before(Str::after($ruta->uri(), 'api/'), '/');
    }

    /**
     * @return array<string, array<string>>
     */
    private function reglas(ReflectionClass $reflexion, object $instancia, bool $creando): array
    {
        $metodo = $reflexion->getMethod('reglas');
        $metodo->setAccessible(true);

        try {
            $reglas = $metodo->invoke($instancia, Request::create('/'), $creando);
        } catch (\Throwable) {
            return [];
        }

        $normalizadas = [];
        foreach ($reglas as $campo => $lista) {
            $lista = is_string($lista) ? explode('|', $lista) : (array) $lista;
            $normalizadas[$campo] = array_values(array_filter($lista, 'is_string'));
        }

        return $normalizadas;
    }

    /**
     * @param  array<string, array<string>>  $reglas
     */
    private function schema(string $nombre, array $reglas, bool $conId, bool $requeridos): string
    {
        $props = [];
        $obligatorios = [];

        if ($conId) {
            $props[] = "        new OA\\Property(property: 'id', type: 'integer', readOnly: true),";
        }

        foreach ($reglas as $campo => $lista) {
            // Reglas de elementos anidados (respuestas.*.campo) se documentan
            // dentro del arreglo padre; aqui solo el nivel raiz.
            if (str_contains($campo, '.')) {
                continue;
            }

            [$tipo, $extras] = $this->tipoDesdeReglas($lista, $reglas, $campo);
            $nullable = in_array('nullable', $lista, true) ? ', nullable: true' : '';
            $props[] = "        new OA\\Property(property: '{$campo}', type: '{$tipo}'{$extras}{$nullable}),";

            if ($requeridos && in_array('required', $lista, true)) {
                $obligatorios[] = $campo;
            }
        }

        if ($conId) {
            $props[] = "        new OA\\Property(property: 'created_at', type: 'string', format: 'date-time', readOnly: true),";
            $props[] = "        new OA\\Property(property: 'updated_at', type: 'string', format: 'date-time', readOnly: true),";
        }

        $lineas = ['#[OA\\Schema(', "    schema: '{$nombre}',", "    type: 'object',"];

        if ($obligatorios !== []) {
            $lineas[] = '    required: ['.implode(', ', array_map(fn ($c) => "'$c'", $obligatorios)).'],';
        }

        $lineas[] = '    properties: [';
        $lineas = array_merge($lineas, $props);
        $lineas[] = '    ],';
        $lineas[] = ')]';

        return implode("\n", $lineas);
    }

    /**
     * Traduce reglas de validacion de Laravel a tipo/formato OpenAPI.
     *
     * @param  array<string>  $lista
     * @param  array<string, array<string>>  $todas
     * @return array{0:string,1:string}
     */
    private function tipoDesdeReglas(array $lista, array $todas, string $campo): array
    {
        $tipo = 'string';
        $extras = [];

        foreach ($lista as $regla) {
            $nombre = Str::before($regla, ':');
            $arg = Str::contains($regla, ':') ? Str::after($regla, ':') : null;

            match ($nombre) {
                'integer', 'exists' => $tipo = 'integer',
                'numeric', 'decimal' => $tipo = 'number',
                'boolean' => $tipo = 'boolean',
                'array' => $tipo = 'array',
                'file', 'image', 'mimes', 'mimetypes' => $extras['format'] = "'binary'",
                'email' => $extras['format'] = "'email'",
                'url' => $extras['format'] = "'uri'",
                'date', 'date_format' => $extras['format'] = $arg === 'Y-m-d' || $nombre === 'date' ? "'date'" : "'date-time'",
                'uuid' => $extras['format'] = "'uuid'",
                'in' => $extras['enum'] = '['.implode(', ', array_map(fn ($v) => "'".addslashes($v)."'", explode(',', (string) $arg))).']',
                'max' => $extras['_max'] = $arg,
                'min' => $extras['_min'] = $arg,
                'size' => $extras['_size'] = $arg,
                'between' => [$extras['_min'], $extras['_max']] = explode(',', (string) $arg) + [null, null],
                'exists_in_same' => null,
                default => null,
            };
        }

        if ($tipo === 'array') {
            $extras['items'] = $this->itemsDeArreglo($todas, $campo);
        }

        $salida = [];
        foreach ($extras as $clave => $valor) {
            if ($valor === null) {
                continue;
            }
            if ($clave === '_max') {
                $salida[] = $tipo === 'string' ? "maxLength: {$valor}" : "maximum: {$valor}";
            } elseif ($clave === '_min') {
                $salida[] = $tipo === 'string' ? "minLength: {$valor}" : "minimum: {$valor}";
            } elseif ($clave === '_size') {
                $salida[] = $tipo === 'string' ? "minLength: {$valor}, maxLength: {$valor}" : '';
            } else {
                $salida[] = "{$clave}: {$valor}";
            }
        }

        $salida = array_filter($salida);

        return [$tipo, $salida === [] ? '' : ', '.implode(', ', $salida)];
    }

    /**
     * @param  array<string, array<string>>  $todas
     */
    private function itemsDeArreglo(array $todas, string $campo): string
    {
        $hijos = [];
        foreach ($todas as $otro => $lista) {
            if (str_starts_with($otro, $campo.'.*.')) {
                $hijos[Str::after($otro, $campo.'.*.')] = $lista;
            }
        }

        if ($hijos === []) {
            if (isset($todas[$campo.'.*'])) {
                [$tipo, $extras] = $this->tipoDesdeReglas($todas[$campo.'.*'], [], $campo.'.*');

                return "new OA\\Items(type: '{$tipo}'{$extras})";
            }

            return "new OA\\Items(type: 'string')";
        }

        $props = [];
        foreach ($hijos as $hijo => $lista) {
            if (str_contains($hijo, '.')) {
                continue;
            }
            [$tipo, $extras] = $this->tipoDesdeReglas($lista, [], $hijo);
            $props[] = "new OA\\Property(property: '{$hijo}', type: '{$tipo}'{$extras})";
        }

        return "new OA\\Items(type: 'object', properties: [".implode(', ', $props).'])';
    }

    /**
     * @param  array<string>  $filtrables
     * @param  array<string>  $ordenables
     * @param  array<string, array<string>>  $reglasCrear
     */
    private function operacion(
        string $metodoHttp,
        string $path,
        string $accion,
        string $tag,
        string $entidad,
        array $filtrables,
        array $ordenables,
        array $reglasCrear,
        string $schemaRecurso,
        string $schemaCrear,
        string $schemaActualizar,
        string $prefijo,
    ): string {
        $atributo = ucfirst(strtolower($metodoHttp));
        $nombre = Str::headline($entidad);
        $l = [];

        switch ($accion) {
            case 'listar':
                $l[] = "    #[OA\\{$atributo}(";
                $l[] = "        path: '{$path}',";
                $l[] = "        tags: ['{$tag}'],";
                $l[] = "        summary: 'Listar {$nombre}',";
                $l[] = "        description: 'Paginado. Filtra por columnas exactas via query string y por rango de creacion (desde/hasta). Solo devuelve lo que esta dentro del alcance del usuario.',";
                $l[] = "        security: [['bearerAuth' => []]],";
                $l[] = '        parameters: [';
                $l[] = "            new OA\\Parameter(ref: '#/components/parameters/pagina'),";
                $l[] = "            new OA\\Parameter(ref: '#/components/parameters/porPagina'),";
                $l[] = "            new OA\\Parameter(ref: '#/components/parameters/desde'),";
                $l[] = "            new OA\\Parameter(ref: '#/components/parameters/hasta'),";
                $l[] = "            new OA\\Parameter(name: 'orden', in: 'query', schema: new OA\\Schema(type: 'string', enum: [".implode(', ', array_map(fn ($o) => "'$o'", $ordenables)).']), description: \'Columna de orden\'),';
                $l[] = "            new OA\\Parameter(ref: '#/components/parameters/direccion'),";
                foreach ($filtrables as $filtro) {
                    [$tipo, $extras] = $this->tipoDesdeReglas($reglasCrear[$filtro] ?? [], [], $filtro);
                    $l[] = "            new OA\\Parameter(name: '{$filtro}', in: 'query', schema: new OA\\Schema(type: '{$tipo}'{$extras}), description: 'Filtro exacto por {$filtro}'),";
                }
                foreach (self::QUERY_EXTRA[$prefijo] ?? [] as [$nombreExtra, $tipoExtra, $descExtra, $formatoExtra]) {
                    $formato = $formatoExtra ? ", format: '{$formatoExtra}'" : '';
                    $l[] = "            new OA\\Parameter(name: '{$nombreExtra}', in: 'query', schema: new OA\\Schema(type: '{$tipoExtra}'{$formato}), description: '{$descExtra}'),";
                }
                $l[] = '        ],';
                $l[] = '        responses: [';
                $l[] = "            new OA\\Response(response: 200, description: 'Pagina de {$nombre}', content: new OA\\JsonContent(allOf: [new OA\\Schema(ref: '#/components/schemas/Paginado'), new OA\\Schema(properties: [new OA\\Property(property: 'data', type: 'array', items: new OA\\Items(ref: '#/components/schemas/{$schemaRecurso}'))])])),";
                $l[] = "            new OA\\Response(response: 401, ref: '#/components/responses/NoAutenticado'),";
                $l[] = "            new OA\\Response(response: 403, ref: '#/components/responses/NoAutorizado'),";
                $l[] = '        ],';
                $l[] = '    )]';
                break;

            case 'ver':
                $l[] = "    #[OA\\{$atributo}(";
                $l[] = "        path: '{$path}',";
                $l[] = "        tags: ['{$tag}'],";
                $l[] = "        summary: 'Ver {$nombre}',";
                $l[] = "        security: [['bearerAuth' => []]],";
                $l[] = "        parameters: [new OA\\Parameter(ref: '#/components/parameters/id')],";
                $l[] = '        responses: [';
                $l[] = "            new OA\\Response(response: 200, description: '{$nombre}', content: new OA\\JsonContent(ref: '#/components/schemas/{$schemaRecurso}')),";
                $l[] = "            new OA\\Response(response: 401, ref: '#/components/responses/NoAutenticado'),";
                $l[] = "            new OA\\Response(response: 403, ref: '#/components/responses/NoAutorizado'),";
                $l[] = "            new OA\\Response(response: 404, ref: '#/components/responses/NoEncontrado'),";
                $l[] = '        ],';
                $l[] = '    )]';
                break;

            case 'crear':
                $l[] = "    #[OA\\{$atributo}(";
                $l[] = "        path: '{$path}',";
                $l[] = "        tags: ['{$tag}'],";
                $l[] = "        summary: 'Crear {$nombre}',";
                $l[] = "        security: [['bearerAuth' => []]],";
                $l[] = "        requestBody: new OA\\RequestBody(required: true, content: new OA\\JsonContent(ref: '#/components/schemas/{$schemaCrear}')),";
                $l[] = '        responses: [';
                $l[] = "            new OA\\Response(response: 201, description: 'Creado', content: new OA\\JsonContent(ref: '#/components/schemas/{$schemaRecurso}')),";
                $l[] = "            new OA\\Response(response: 401, ref: '#/components/responses/NoAutenticado'),";
                $l[] = "            new OA\\Response(response: 403, ref: '#/components/responses/NoAutorizado'),";
                $l[] = "            new OA\\Response(response: 422, ref: '#/components/responses/Validacion'),";
                $l[] = '        ],';
                $l[] = '    )]';
                break;

            case 'actualizar':
                $l[] = "    #[OA\\{$atributo}(";
                $l[] = "        path: '{$path}',";
                $l[] = "        tags: ['{$tag}'],";
                $l[] = "        summary: 'Actualizar {$nombre}',";
                $l[] = "        description: 'PUT y PATCH son equivalentes: todos los campos son opcionales y solo se escriben los enviados.',";
                $l[] = "        security: [['bearerAuth' => []]],";
                $l[] = "        parameters: [new OA\\Parameter(ref: '#/components/parameters/id')],";
                $l[] = "        requestBody: new OA\\RequestBody(required: true, content: new OA\\JsonContent(ref: '#/components/schemas/{$schemaActualizar}')),";
                $l[] = '        responses: [';
                $l[] = "            new OA\\Response(response: 200, description: 'Actualizado', content: new OA\\JsonContent(ref: '#/components/schemas/{$schemaRecurso}')),";
                $l[] = "            new OA\\Response(response: 401, ref: '#/components/responses/NoAutenticado'),";
                $l[] = "            new OA\\Response(response: 403, ref: '#/components/responses/NoAutorizado'),";
                $l[] = "            new OA\\Response(response: 404, ref: '#/components/responses/NoEncontrado'),";
                $l[] = "            new OA\\Response(response: 422, ref: '#/components/responses/Validacion'),";
                $l[] = '        ],';
                $l[] = '    )]';
                break;

            case 'eliminar':
                $l[] = "    #[OA\\{$atributo}(";
                $l[] = "        path: '{$path}',";
                $l[] = "        tags: ['{$tag}'],";
                $l[] = "        summary: 'Eliminar {$nombre}',";
                $l[] = "        description: 'Los modelos clinicos usan borrado logico: el dato no se pierde.',";
                $l[] = "        security: [['bearerAuth' => []]],";
                $l[] = "        parameters: [new OA\\Parameter(ref: '#/components/parameters/id')],";
                $l[] = '        responses: [';
                $l[] = "            new OA\\Response(response: 204, description: 'Eliminado'),";
                $l[] = "            new OA\\Response(response: 401, ref: '#/components/responses/NoAutenticado'),";
                $l[] = "            new OA\\Response(response: 403, ref: '#/components/responses/NoAutorizado'),";
                $l[] = "            new OA\\Response(response: 404, ref: '#/components/responses/NoEncontrado'),";
                $l[] = "            new OA\\Response(response: 409, ref: '#/components/responses/Conflicto'),";
                $l[] = '        ],';
                $l[] = '    )]';
                break;
        }

        return implode("\n", $l);
    }
}
