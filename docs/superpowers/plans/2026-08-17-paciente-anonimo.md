# Paciente anónimo — plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Un paciente puede usar la API sin registrarse (precalificación, subida de estudios, perfil) con una identidad temporal, y al registrarse por Auth0 esa misma fila de `users`/`pacientes` se convierte en su cuenta definitiva.

**Architecture:** `POST /auth/anonimo` crea `User` (sin email) + `Paciente` y devuelve un token Sanctum; el resto de la API no cambia porque el anónimo es un `paciente` normal acotado por `Alcance`. `POST /auth/auth0` lee opcionalmente ese Bearer y, si el usuario es temporal, escribe `email`/`auth0Sub` sobre la misma fila (reclamo). Un comando diario purga anónimos sin actividad; un callback de Sanctum exime al token anónimo de la caducidad global de 24 h.

**Tech Stack:** Laravel 13 (PHP 8.3), Sanctum 4, PHPUnit (sqlite en memoria vía `phpunit.xml`), Pint.

**Spec:** `docs/superpowers/specs/2026-08-17-paciente-anonimo-design.md` — leerla entera antes de empezar; cada tarea de abajo la implementa por partes.

## Global Constraints

- Idioma del código, comentarios y nombres de tests: español sin tildes en identificadores, igual que el resto del repo (`esTemporal`, `reclamar`, `test_el_anonimo_...`).
- Nombres de columnas en camelCase (`usuarioId`, `auth0Sub`), tablas en snake_case, como las migraciones existentes.
- Todo test es Feature con `use RefreshDatabase;` y corre con `php artisan test --filter=NombreDelTest`. La salida del runner es una línea JSON tipo `{"tool":"phpunit","result":"passed",...}`; si `result` no es `passed`, correr `./vendor/bin/phpunit --filter=NombreDelTest` para ver el detalle.
- Antes de cada commit: `./vendor/bin/pint --dirty` (formatea solo lo modificado). El commit anterior del repo fue justamente "style: apply Pint formatting"; no dejar código sin pasar por Pint.
- Mensajes de commit en inglés estilo conventional (`feat:`, `test:`), como `git log` muestra. Terminar cada mensaje con la línea `Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>`.
- Trabajar en rama `feature/paciente-anonimo` creada desde `main`. Si el árbol tiene cambios ajenos sin commitear (hoy hay: `SolicitudAccesoDoctor*`, `routes/api.php`, `TipoEstudioController.php`), commitearlos o guardarlos con `git stash` **antes** de empezar; este plan no los toca.
- Bug preexistente, fuera de este plan: `App\Http\Controllers\TipoEstudioController::listar()` redeclara el método con firma incompatible con `BaseCrudController::listar(Request $request): JsonResponse` y provoca un fatal al cargar la clase (`php artisan route:list` falla con "Declaration of App\Http\Controllers\TipoEstudioController::listar..."). Si estorba para verificar rutas, borrar ese override; no lo "arregles" dentro de un commit de este plan.
- Mensajes de error de la API en español sin tildes, entre comillas simples, igual que los existentes (`'El usuario no tiene perfil de paciente.'`).
- No tocar `Alcance`, `BaseCrudController` ni las reglas de ningún CRUD: el anónimo funciona con lo que ya hay.

---

### Task 1: `users.email` nullable y `User::esTemporal()`

**Files:**
- Create: `database/migrations/2026_08_17_100000_make_users_email_nullable.php`
- Modify: `app/Models/User.php`
- Test: `tests/Feature/UsuarioTemporalTest.php`

**Interfaces:**
- Consumes: nada nuevo.
- Produces: `User::NOMBRE_TEMPORAL` (string `'Paciente'`), `User::esTemporal(): bool`, atributo JSON `esTemporal` (bool) en toda serialización de `User`.

- [ ] **Step 1: Escribir el test que falla**

Crear `tests/Feature/UsuarioTemporalTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuarioTemporalTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_usuario_puede_nacer_sin_correo_y_es_temporal(): void
    {
        $usuario = User::create([
            'name' => User::NOMBRE_TEMPORAL,
            'email' => null,
            'password' => null,
            'rol' => User::ROL_PACIENTE,
        ]);

        $fresco = $usuario->fresh();

        $this->assertNull($fresco->email);
        $this->assertTrue($fresco->esTemporal());
        // La app decide con esto si pedir registro: tiene que viajar en el JSON.
        $this->assertTrue($fresco->toArray()['esTemporal']);
    }

    public function test_varios_anonimos_conviven_y_el_correo_sigue_siendo_unico(): void
    {
        User::create(['name' => User::NOMBRE_TEMPORAL, 'email' => null, 'password' => null, 'rol' => User::ROL_PACIENTE]);
        User::create(['name' => User::NOMBRE_TEMPORAL, 'email' => null, 'password' => null, 'rol' => User::ROL_PACIENTE]);

        $this->assertSame(2, User::whereNull('email')->count());

        // El indice unico tiene que sobrevivir al change() de la migracion.
        User::factory()->create(['email' => 'ana@ejemplo.com']);

        $this->expectException(QueryException::class);
        User::factory()->create(['email' => 'ana@ejemplo.com']);
    }

    public function test_un_usuario_con_correo_no_es_temporal(): void
    {
        $usuario = User::factory()->create();

        $this->assertFalse($usuario->esTemporal());
        $this->assertFalse($usuario->toArray()['esTemporal']);
    }
}
```

- [ ] **Step 2: Correr el test y verificar que falla**

Run: `php artisan test --filter=UsuarioTemporalTest`
Expected: FAIL — `Undefined constant App\Models\User::NOMBRE_TEMPORAL` (y, si se llegara más lejos, `NOT NULL constraint failed: users.email`).

- [ ] **Step 3: Migración**

Crear `database/migrations/2026_08_17_100000_make_users_email_nullable.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Un paciente anonimo (POST /auth/anonimo) nace sin correo: lo aporta
            // Auth0 recien al reclamar la cuenta. El indice unico se conserva:
            // MySQL y SQLite admiten varios NULL en una columna unique.
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Advertencia: restaurar NOT NULL falla si existen anonimos vivos.
            // Un fallo ruidoso es preferible a inventar un correo o borrarlos.
            $table->string('email')->nullable(false)->change();
        });
    }
};
```

- [ ] **Step 4: Modelo**

En `app/Models/User.php`, después de `public const ROL_PACIENTE = 'paciente';` agregar:

```php
    /** Nombre con el que nace un paciente anonimo hasta que lo complete o lo reclame. */
    public const NOMBRE_TEMPORAL = 'Paciente';
```

Después del bloque `protected $hidden = [...]` agregar:

```php
    /** @var array<int, string> */
    protected $appends = ['esTemporal'];
```

Al final de la clase, después de `esPaciente()`, agregar:

```php
    /**
     * Paciente anonimo: entro por POST /auth/anonimo y todavia no reclamo la
     * cuenta con Auth0. Se deriva del correo para que no exista un estado que
     * pueda quedar desincronizado: al reclamar se escribe el email y deja de
     * ser temporal solo.
     */
    public function esTemporal(): bool
    {
        return $this->email === null;
    }

    public function getEsTemporalAttribute(): bool
    {
        return $this->esTemporal();
    }
```

- [ ] **Step 5: Correr el test y verificar que pasa**

Run: `php artisan test --filter=UsuarioTemporalTest`
Expected: `"result":"passed"`, 3 tests.

- [ ] **Step 6: Suite completa (regresión por `$appends`)**

Run: `php artisan test`
Expected: `"result":"passed"`. Si algún test viejo compara JSON exacto de un usuario y falla por la clave nueva `esTemporal`, agregar la clave al esperado de ese test (no quitar el `$appends`).

- [ ] **Step 7: Commit**

```bash
./vendor/bin/pint --dirty
git add database/migrations/2026_08_17_100000_make_users_email_nullable.php app/Models/User.php tests/Feature/UsuarioTemporalTest.php
git commit -m "feat: allow users without email and expose esTemporal

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 2: `SesionOpcional` — leer el Bearer en rutas públicas

**Files:**
- Create: `app/Support/SesionOpcional.php`
- Test: `tests/Feature/SesionOpcionalTest.php`

**Interfaces:**
- Consumes: `App\Models\User`.
- Produces: `App\Support\SesionOpcional::usuario(Request $request): ?User` — devuelve el `User` del Bearer; `null` si no hay Bearer; aborta `401` con mensaje `'El token de la sesion no es valido.'` si hay Bearer pero no resuelve.

- [ ] **Step 1: Escribir el test que falla**

Crear `tests/Feature/SesionOpcionalTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\SesionOpcional;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SesionOpcionalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ruta publica de prueba, sin auth:sanctum, igual que /auth/auth0.
        Route::get('/_prueba/sesion-opcional', fn (Request $request) => response()->json([
            'id' => SesionOpcional::usuario($request)?->id,
        ]));
    }

    public function test_sin_bearer_no_hay_sesion(): void
    {
        $this->getJson('/_prueba/sesion-opcional')
            ->assertOk()
            ->assertJsonPath('id', null);
    }

    public function test_con_bearer_valido_devuelve_al_usuario(): void
    {
        $usuario = User::factory()->create();
        $token = $usuario->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->getJson('/_prueba/sesion-opcional')
            ->assertOk()
            ->assertJsonPath('id', $usuario->id);
    }

    // Un Bearer presente pero invalido no se ignora en silencio: si la app cree
    // que reclama la cuenta y no la reclama, el paciente pierde sus estudios.
    public function test_con_bearer_invalido_responde_401(): void
    {
        $this->withToken('1|token-que-no-existe')
            ->getJson('/_prueba/sesion-opcional')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'El token de la sesion no es valido.');
    }

    public function test_un_bearer_revocado_responde_401(): void
    {
        $usuario = User::factory()->create();
        $token = $usuario->createToken('api')->plainTextToken;
        $usuario->tokens()->delete();

        $this->withToken($token)
            ->getJson('/_prueba/sesion-opcional')
            ->assertUnauthorized();
    }
}
```

- [ ] **Step 2: Correr el test y verificar que falla**

Run: `php artisan test --filter=SesionOpcionalTest`
Expected: FAIL — `Class "App\Support\SesionOpcional" not found`.

- [ ] **Step 3: Implementar el helper**

Crear `app/Support/SesionOpcional.php`:

```php
<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Sesion Sanctum en rutas publicas.
 *
 * Las rutas del onboarding (alta por Auth0, precalificacion) no exigen sesion,
 * pero si el paciente anonimo manda su Bearer tienen que reconocerlo. Un Bearer
 * presente pero invalido no se ignora en silencio: si la app cree que reclama
 * la cuenta y no la reclama, el paciente pierde sus estudios sin enterarse.
 */
class SesionOpcional
{
    public static function usuario(Request $request): ?User
    {
        // El guard de Sanctum resuelve el Bearer aunque la ruta no lleve el
        // middleware auth:sanctum; tambien cubre actingAs() en tests.
        $usuario = $request->user('sanctum');

        if ($usuario instanceof User) {
            return $usuario;
        }

        abort_if($request->bearerToken() !== null, 401, 'El token de la sesion no es valido.');

        return null;
    }
}
```

- [ ] **Step 4: Correr el test y verificar que pasa**

Run: `php artisan test --filter=SesionOpcionalTest`
Expected: `"result":"passed"`, 4 tests.

- [ ] **Step 5: Commit**

```bash
./vendor/bin/pint --dirty
git add app/Support/SesionOpcional.php tests/Feature/SesionOpcionalTest.php
git commit -m "feat: add SesionOpcional to read an optional Sanctum bearer on public routes

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 3: Alta anónima (`POST /api/auth/anonimo`) y token sin caducidad global

**Files:**
- Create: `app/Http/Controllers/Auth/SesionAnonimaController.php`
- Modify: `app/Providers/AppServiceProvider.php` (método `boot`)
- Modify: `routes/api.php` (bloque `rutas publicas`, después de `/precalificacion/evaluar`)
- Test: `tests/Feature/Auth/SesionAnonimaTest.php`

**Interfaces:**
- Consumes: `User::NOMBRE_TEMPORAL`, `User::esTemporal()` (Task 1).
- Produces: ruta `POST /api/auth/anonimo` → `201 { token, usuario }`; los tests de Task 4 y Task 5 la usan para fabricar anónimos reales.

- [ ] **Step 1: Escribir el test que falla**

Crear `tests/Feature/Auth/SesionAnonimaTest.php`:

```php
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
```

- [ ] **Step 2: Correr el test y verificar que falla**

Run: `php artisan test --filter=SesionAnonimaTest`
Expected: FAIL — los `postJson('/api/auth/anonimo')` devuelven 404 (`assertCreated` falla) y `test_el_token_de_una_cuenta_real_sigue_caducando_a_las_24_horas` PASA (es regresión; debe seguir pasando al final).

- [ ] **Step 3: Controlador**

Crear `app/Http/Controllers/Auth/SesionAnonimaController.php`:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Alta de un paciente anonimo.
 *
 * Hasta la entrega de estudios el paciente no se registra. Esta ruta le da una
 * identidad temporal (User + Paciente sin correo) y un token Sanctum con el
 * que usa la API como cualquier paciente. Al registrarse por Auth0 mandando
 * ese mismo Bearer, Auth0SessionController rellena la misma fila: nada se
 * mueve de tabla.
 *
 * La credencial es el token, nunca el id: el id es secuencial y con el
 * cualquiera leeria estudios ajenos.
 */
class SesionAnonimaController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'dispositivo' => ['sometimes', 'string', 'max:100'],
        ]);

        $usuario = DB::transaction(function () use ($request) {
            $usuario = User::create([
                'name' => User::NOMBRE_TEMPORAL,
                'email' => null,
                'auth0Sub' => null,
                'password' => null,
                'rol' => User::ROL_PACIENTE,
            ]);

            // Mismo paciente minimo que crea el alta por Auth0: sin esta fila
            // Alcance::pacienteId() no lo encuentra y no podria subir estudios.
            Paciente::create(['usuarioId' => $usuario->id]);

            AuditLog::create([
                'usuarioId' => $usuario->id,
                'entidad' => class_basename($usuario),
                'entidadId' => $usuario->getKey(),
                'accion' => 'crear-anonimo',
                'antes' => null,
                'despues' => $usuario->toArray(),
                'ip' => $request->ip(),
            ]);

            return $usuario;
        });

        return response()->json([
            'token' => $usuario->createToken($datos['dispositivo'] ?? 'api')->plainTextToken,
            'usuario' => $usuario->load(['doctor.clinica', 'paciente']),
        ], 201);
    }
}
```

- [ ] **Step 4: Ruta**

En `routes/api.php`:

1. Agregar el `use` en orden alfabético, junto al de `Auth0SessionController`:

```php
use App\Http\Controllers\Auth\SesionAnonimaController;
```

2. En el bloque `// ===== rutas publicas`, justo después de la ruta `/precalificacion/evaluar`, agregar:

```php
// El paciente empieza sin cuenta: identidad temporal + token. La reclama
// despues en POST /auth/auth0 mandando este mismo Bearer.
Route::post('/auth/anonimo', [SesionAnonimaController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('auth.anonimo');
```

- [ ] **Step 5: Eximir al token anónimo de la caducidad global**

En `app/Providers/AppServiceProvider.php`, agregar los `use`:

```php
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
```

y dentro de `boot()`, después del bloque `ResetPassword::createUrlUsing(...)`:

```php
        // sanctum.expiration vence todo token a las 24 h y la app lo renueva
        // en silencio via Auth0. El paciente anonimo no tiene Auth0 con que
        // renovar: su token vale hasta que reclame la cuenta (se revocan todos)
        // o hasta que la purga por inactividad lo de de baja.
        Sanctum::authenticateAccessTokensUsing(function (PersonalAccessToken $token, bool $esValido): bool {
            $usuario = $token->tokenable;

            if ($usuario instanceof User && $usuario->esTemporal()) {
                return $token->expires_at === null || ! $token->expires_at->isPast();
            }

            return $esValido;
        });
```

- [ ] **Step 6: Correr el test y verificar que pasa**

Run: `php artisan test --filter=SesionAnonimaTest`
Expected: `"result":"passed"`, 7 tests.

- [ ] **Step 7: Suite completa**

Run: `php artisan test`
Expected: `"result":"passed"`.

- [ ] **Step 8: Commit**

```bash
./vendor/bin/pint --dirty
git add app/Http/Controllers/Auth/SesionAnonimaController.php app/Providers/AppServiceProvider.php routes/api.php tests/Feature/Auth/SesionAnonimaTest.php
git commit -m "feat: anonymous patient sign-up with a non-expiring Sanctum token

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 4: Reclamo de la cuenta en `POST /api/auth/auth0`

**Files:**
- Modify: `app/Http/Controllers/Auth/Auth0SessionController.php`
- Test: `tests/Feature/Auth/ReclamoCuentaAnonimaTest.php`

**Interfaces:**
- Consumes: `SesionOpcional::usuario()` (Task 2), `User::esTemporal()` / `User::NOMBRE_TEMPORAL` (Task 1), `POST /api/auth/anonimo` (Task 3), `App\Support\Auth0\PerfilAuth0(string $sub, ?string $email, bool $emailVerificado, ?string $nombre)` y la interfaz `App\Support\Auth0\VerificadorAuth0` (ya existen).
- Produces: `POST /api/auth/auth0` con Bearer de anónimo → mismo `users.id` con `email`/`auth0Sub` rellenos; `409` si el correo/sub ya tiene cuenta; `AuditLog` con `accion = 'reclamar'`.

- [ ] **Step 1: Escribir el test que falla**

Crear `tests/Feature/Auth/ReclamoCuentaAnonimaTest.php`:

```php
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
        $estudioId = $this->withToken($token)->postJson('/api/estudios-medicos', [
            'tipoEstudioId' => $tipo->id,
            'fecha' => now()->toDateString(),
        ])->assertCreated()->json('id');

        $this->fingirPerfil(new PerfilAuth0('auth0|ana', 'Ana@Ejemplo.com', true, 'Ana Ibarra'));

        $respuesta = $this->withToken($token)->postJson('/api/auth/auth0', ['accessToken' => 'da-igual']);

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
        $this->withToken($respuesta->json('token'))
            ->getJson("/api/estudios-medicos/{$estudioId}")
            ->assertOk()
            ->assertJsonPath('pacienteId', $pacienteId);

        // El reclamo cierra la sesion anonima.
        $this->withToken($token)->getJson('/api/user')->assertUnauthorized();

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

        $this->withToken($token)->patchJson('/api/perfil', ['name' => 'Ana'])->assertOk();

        $this->fingirPerfil(new PerfilAuth0('auth0|ana', 'ana@ejemplo.com', true, 'Nombre De Google'));

        $this->withToken($token)->postJson('/api/auth/auth0', ['accessToken' => 'da-igual'])
            ->assertOk()
            ->assertJsonPath('usuario.name', 'Ana');
    }

    public function test_si_el_correo_ya_tiene_cuenta_responde_409_y_el_anonimo_queda_intacto(): void
    {
        User::factory()->create(['email' => 'ana@ejemplo.com']);
        [$token, $anonimo] = $this->altaAnonima();

        $this->fingirPerfil(new PerfilAuth0('auth0|ana', 'ana@ejemplo.com', true, 'Ana'));

        $this->withToken($token)->postJson('/api/auth/auth0', ['accessToken' => 'da-igual'])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Ya existe una cuenta con este correo. Inicia sesion con ella.');

        $this->assertNull($anonimo->fresh()->email);
        $this->assertSame(2, User::count());
        // El anonimo sigue vivo: su token todavia sirve.
        $this->withToken($token)->getJson('/api/user')->assertOk();
    }

    public function test_si_el_sub_ya_tiene_cuenta_responde_409(): void
    {
        User::factory()->conAuth0('auth0|ana')->create(['email' => 'vieja@ejemplo.com']);
        [$token, $anonimo] = $this->altaAnonima();

        $this->fingirPerfil(new PerfilAuth0('auth0|ana', 'nueva@ejemplo.com', true, 'Ana'));

        $this->withToken($token)->postJson('/api/auth/auth0', ['accessToken' => 'da-igual'])->assertStatus(409);

        $this->assertNull($anonimo->fresh()->email);
    }

    // Va antes que el 409: un correo sin verificar no debe poder sondear si
    // existe una cuenta.
    public function test_un_correo_sin_verificar_no_reclama(): void
    {
        User::factory()->create(['email' => 'ana@ejemplo.com']);
        [$token, $anonimo] = $this->altaAnonima();

        $this->fingirPerfil(new PerfilAuth0('auth0|ana', 'ana@ejemplo.com', false, 'Ana'));

        $this->withToken($token)->postJson('/api/auth/auth0', ['accessToken' => 'da-igual'])
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

        $this->withToken($token)->postJson('/api/auth/auth0', ['accessToken' => 'da-igual'])->assertForbidden();

        $this->assertNull($anonimo->fresh()->email);
    }

    public function test_un_bearer_invalido_responde_401_y_no_crea_nada(): void
    {
        $this->fingirPerfil(new PerfilAuth0('auth0|ana', 'ana@ejemplo.com', true, 'Ana'));

        $this->withToken('1|token-inexistente')
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

        $this->withToken($token)->postJson('/api/auth/auth0', ['accessToken' => 'da-igual'])
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
```

- [ ] **Step 2: Correr el test y verificar que falla**

Run: `php artisan test --filter=ReclamoCuentaAnonimaTest`
Expected: FAIL. En concreto `test_el_anonimo_reclama_su_cuenta_sobre_la_misma_fila` falla en `assertJsonPath('usuario.id', ...)` (hoy el Bearer se ignora y se crea un segundo usuario), y `test_un_bearer_invalido_responde_401_y_no_crea_nada` recibe 200. Los dos últimos tests (regresión) deben pasar ya.

- [ ] **Step 3: Implementar el reclamo**

En `app/Http/Controllers/Auth/Auth0SessionController.php`:

1. Agregar el `use`:

```php
use App\Support\SesionOpcional;
```

2. Reemplazar el método `store` completo por:

```php
    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'accessToken' => ['required', 'string'],
            'dispositivo' => ['sometimes', 'string', 'max:100'],
        ]);

        // Si viene el Bearer de un paciente anonimo, este login es un reclamo:
        // la cuenta que devuelva Auth0 se escribe sobre esa misma fila. El
        // Bearer de una cuenta ya real se ignora: es el re-login de siempre.
        $sesion = SesionOpcional::usuario($request);
        $anonimo = $sesion?->esTemporal() ? $sesion : null;

        try {
            $perfil = $this->verificador->verificar($datos['accessToken']);
        } catch (TokenAuth0Invalido $e) {
            abort(401, 'Access token de Auth0 invalido.');
        } catch (Auth0NoDisponible $e) {
            abort(503, 'El proveedor de identidad no esta disponible.');
        }

        abort_if(
            blank($perfil->email),
            422,
            'Auth0 no entrego un correo para esta cuenta.'
        );

        $usuario = DB::transaction(fn () => $this->resolverUsuario($request, $perfil, $anonimo));

        $nombreToken = $datos['dispositivo'] ?? 'api';

        if ($anonimo !== null) {
            // El reclamo cierra la sesion anonima: la app guarda el token nuevo.
            $usuario->tokens()->delete();
        } else {
            // Un token por dispositivo: reentrar desde el mismo equipo no acumula.
            $usuario->tokens()->where('name', $nombreToken)->delete();
        }

        return response()->json([
            'token' => $usuario->createToken($nombreToken)->plainTextToken,
            'usuario' => $usuario->load(['doctor.clinica', 'paciente']),
        ]);
    }
```

3. Cambiar la firma de `resolverUsuario` y desviar al reclamo al principio. La primera línea del método ya normaliza el email; el resto del cuerpo **no cambia**:

```php
    private function resolverUsuario(Request $request, PerfilAuth0 $perfil, ?User $anonimo): User
    {
        // Normalizar una sola vez: Auth0 puede devolver mayusculas o espacios
        // y el alta local (UsuarioController) siempre guarda en minusculas.
        // Sin esto, "Maria@Ejemplo.com" no encuentra a "maria@ejemplo.com" y
        // el doctor termina con una cuenta de paciente nueva.
        $email = trim(mb_strtolower($perfil->email));

        if ($anonimo !== null) {
            return $this->reclamar($request, $perfil, $email, $anonimo);
        }

        $porSub = User::where('auth0Sub', $perfil->sub)->first();
        // ... (resto del metodo tal cual esta hoy)
```

4. Agregar el método nuevo justo después de `resolverUsuario`:

```php
    /**
     * Convierte al paciente anonimo en la cuenta que devuelve Auth0.
     *
     * Misma fila de users y de pacientes: estudios, archivos y precalificacion
     * ya cuelgan de ella, asi que no se mueve nada. Solo se rellenan las
     * columnas que el anonimo no tenia.
     */
    private function reclamar(Request $request, PerfilAuth0 $perfil, string $email, User $anonimo): User
    {
        // Primero: un correo sin verificar no puede ni sondear si existe cuenta.
        abort_unless($perfil->emailVerificado, 422, 'Auth0 no ha verificado este correo para reclamar la cuenta.');

        // Misma regla que el alta: una baja no revive por un login.
        abort_if(
            User::onlyTrashed()->where(function ($consulta) use ($email, $perfil) {
                $consulta->where('email', $email)->orWhere('auth0Sub', $perfil->sub);
            })->exists(),
            403,
            'Esta cuenta esta dada de baja. Contacta con soporte.'
        );

        // Fusionar dos personas implica reasignar decenas de tablas: fuera de
        // alcance. El anonimo queda intacto y la app ofrece iniciar sesion.
        abort_if(
            User::where(function ($consulta) use ($email, $perfil) {
                $consulta->where('email', $email)->orWhere('auth0Sub', $perfil->sub);
            })->exists(),
            409,
            'Ya existe una cuenta con este correo. Inicia sesion con ella.'
        );

        $antes = $anonimo->toArray();

        $anonimo->update([
            'email' => $email,
            'auth0Sub' => $perfil->sub,
            // Solo se pisa el placeholder: pudo completar su nombre por /perfil.
            'name' => $anonimo->name === User::NOMBRE_TEMPORAL ? ($perfil->nombre ?? $email) : $anonimo->name,
            'email_verified_at' => now(),
        ]);

        $this->auditar($request, 'reclamar', $anonimo, $antes, $anonimo->toArray());

        return $anonimo;
    }
```

5. Actualizar el docblock de la clase (arriba de `class Auth0SessionController`) agregando un párrafo:

```php
 *
 * Si la peticion trae el Bearer de un paciente anonimo (POST /auth/anonimo),
 * el intercambio ademas reclama esa cuenta: escribe email y auth0Sub sobre la
 * misma fila en vez de crear un usuario nuevo.
```

- [ ] **Step 4: Correr el test y verificar que pasa**

Run: `php artisan test --filter=ReclamoCuentaAnonimaTest`
Expected: `"result":"passed"`, 9 tests.

- [ ] **Step 5: Regresión de Auth0**

Run: `php artisan test --filter="Auth0LoginTest|PrecalificacionVincularTest|UsuarioAuth0Test"`
Expected: `"result":"passed"`. Estos tests entran sin Bearer y no deben cambiar.

- [ ] **Step 6: Commit**

```bash
./vendor/bin/pint --dirty
git add app/Http/Controllers/Auth/Auth0SessionController.php tests/Feature/Auth/ReclamoCuentaAnonimaTest.php
git commit -m "feat: claim an anonymous patient account on Auth0 login

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 5: Precalificación a nombre del anónimo

**Files:**
- Modify: `app/Http/Controllers/PrecalificacionController.php` (método `evaluar`)
- Test: `tests/Feature/PrecalificacionEvaluarAnonimoTest.php`

**Interfaces:**
- Consumes: `SesionOpcional::usuario()` (Task 2), `Alcance::pacienteId(User): ?int` (existe), `POST /api/auth/anonimo` (Task 3), `Database\Seeders\PreguntaPrecalificacionSeeder` (existe; siembra 9 preguntas activas, la `q1` alarma con `'no'`, las demás con `'si'`).
- Produces: `POST /api/precalificacion/evaluar` con Bearer de paciente guarda `pacienteId` propio e ignora el del cuerpo.

- [ ] **Step 1: Escribir el test que falla**

Crear `tests/Feature/PrecalificacionEvaluarAnonimoTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Paciente;
use App\Models\Precalificacion;
use App\Models\User;
use Database\Seeders\PreguntaPrecalificacionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrecalificacionEvaluarAnonimoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PreguntaPrecalificacionSeeder::class);
    }

    public function test_con_bearer_anonimo_la_precalificacion_queda_a_su_nombre(): void
    {
        $respuestas = $this->respuestasAptas();

        $alta = $this->postJson('/api/auth/anonimo')->assertCreated();
        $propio = Paciente::where('usuarioId', $alta->json('usuario.id'))->sole();

        $otroUsuario = User::factory()->create(['rol' => User::ROL_PACIENTE]);
        $otro = Paciente::create(['usuarioId' => $otroUsuario->id]);

        // El pacienteId del cuerpo se ignora: manda la sesion.
        $this->withToken($alta->json('token'))->postJson('/api/precalificacion/evaluar', [
            'pacienteId' => $otro->id,
            'respuestas' => $respuestas,
        ])->assertCreated()->assertJsonPath('pacienteId', $propio->id);

        $this->assertSame($propio->id, Precalificacion::sole()->pacienteId);
    }

    public function test_sin_bearer_sigue_siendo_anonima_con_lead_email(): void
    {
        $respuestas = $this->respuestasAptas();

        $this->postJson('/api/precalificacion/evaluar', [
            'leadEmail' => 'lead@glucy.test',
            'respuestas' => $respuestas,
        ])->assertCreated()
            ->assertJsonPath('pacienteId', null)
            ->assertJsonPath('leadEmail', 'lead@glucy.test');
    }

    public function test_un_bearer_invalido_responde_401(): void
    {
        $respuestas = $this->respuestasAptas();

        $this->withToken('1|token-inexistente')->postJson('/api/precalificacion/evaluar', [
            'respuestas' => $respuestas,
        ])->assertUnauthorized();

        $this->assertSame(0, Precalificacion::count());
    }

    /**
     * Todas las preguntas activas respondidas sin disparar ninguna alarma.
     * Se calcula ANTES de fijar el Bearer del test para no arrastrarlo.
     *
     * @return array<int, array{preguntaId: int, respuesta: string}>
     */
    private function respuestasAptas(): array
    {
        return collect($this->getJson('/api/precalificacion/preguntas')->assertOk()->json())
            ->map(fn (array $p) => [
                'preguntaId' => $p['id'],
                // q1 ("¿Tienes 18 años o más?") alarma con 'no'; el resto con 'si'.
                'respuesta' => $p['codigo'] === 'q1' ? 'si' : 'no',
            ])
            ->all();
    }
}
```

- [ ] **Step 2: Correr el test y verificar que falla**

Run: `php artisan test --filter=PrecalificacionEvaluarAnonimoTest`
Expected: FAIL — el primer test guarda `pacienteId` del otro (assertJsonPath falla) y el tercero recibe 201 en vez de 401. El segundo pasa (regresión).

- [ ] **Step 3: Implementar**

En `app/Http/Controllers/PrecalificacionController.php`:

1. Agregar el `use`:

```php
use App\Support\SesionOpcional;
```

2. En `evaluar()`, justo después del bloque `$datos = $request->validate([...]);` y antes de `$preguntas = PreguntaPrecalificacion::whereIn(...)`, insertar:

```php
        // Con el Bearer de un paciente (anonimo o no) la precalificacion queda
        // a su nombre; el pacienteId del cuerpo se ignora, igual que hace el
        // CRUD con forzarPacientePropio. Sin Bearer sigue siendo el flujo
        // anonimo por leadEmail.
        $sesion = SesionOpcional::usuario($request);

        if ($sesion !== null && $sesion->esPaciente()) {
            $pacienteId = Alcance::pacienteId($sesion);

            abort_if($pacienteId === null, 422, 'El usuario no tiene perfil de paciente.');

            $datos['pacienteId'] = $pacienteId;
        }
```

3. Actualizar el docblock de `evaluar()` agregando al final:

```php
     *
     * Si viene el Bearer de un paciente, el resultado se ata a ese paciente.
```

- [ ] **Step 4: Correr el test y verificar que pasa**

Run: `php artisan test --filter=PrecalificacionEvaluarAnonimoTest`
Expected: `"result":"passed"`, 3 tests.

- [ ] **Step 5: Regresión del flujo clínico**

Run: `php artisan test --filter="FlujoClinicoTest|PrecalificacionVincularTest"`
Expected: `"result":"passed"`.

- [ ] **Step 6: Commit**

```bash
./vendor/bin/pint --dirty
git add app/Http/Controllers/PrecalificacionController.php tests/Feature/PrecalificacionEvaluarAnonimoTest.php
git commit -m "feat: bind precalificacion to the authenticated patient when a bearer is sent

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 6: Purga de anónimos abandonados

**Files:**
- Create: `app/Console/Commands/PurgarUsuariosAnonimos.php`
- Modify: `config/auth.php` (antes del `];` final)
- Modify: `routes/console.php`
- Modify: `.env.example` (al final)
- Test: `tests/Feature/PurgarUsuariosAnonimosTest.php`

**Interfaces:**
- Consumes: `User::NOMBRE_TEMPORAL` (Task 1), relación `User::tokens()` (Sanctum, existe), `Paciente` con `SoftDeletes` (existe).
- Produces: comando `usuarios:purgar-anonimos {--dias=} {--dry-run}`; clave de config `auth.anonimos.dias_vigencia` (int, default 30); tarea programada diaria.

- [ ] **Step 1: Escribir el test que falla**

Crear `tests/Feature/PurgarUsuariosAnonimosTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurgarUsuariosAnonimosTest extends TestCase
{
    use RefreshDatabase;

    public function test_purga_al_anonimo_viejo_sin_actividad(): void
    {
        $viejo = $this->anonimoDeHace(40);

        $this->artisan('usuarios:purgar-anonimos')->assertSuccessful();

        $this->assertSoftDeleted('users', ['id' => $viejo->id]);
        $this->assertSoftDeleted('pacientes', ['usuarioId' => $viejo->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $viejo->id]);
        $this->assertDatabaseHas('audit_logs', [
            'usuarioId' => null,
            'entidad' => 'User',
            'entidadId' => $viejo->id,
            'accion' => 'purgar-anonimo',
        ]);
    }

    public function test_conserva_al_anonimo_viejo_con_token_usado_hace_poco(): void
    {
        $activo = $this->anonimoDeHace(40, tokenUsadoHace: 2);

        $this->artisan('usuarios:purgar-anonimos')->assertSuccessful();

        $this->assertNotSoftDeleted('users', ['id' => $activo->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $activo->id]);
    }

    public function test_conserva_al_anonimo_reciente(): void
    {
        $reciente = $this->anonimoDeHace(5);

        $this->artisan('usuarios:purgar-anonimos')->assertSuccessful();

        $this->assertNotSoftDeleted('users', ['id' => $reciente->id]);
    }

    public function test_nunca_toca_usuarios_con_correo(): void
    {
        $registrado = User::factory()->create(['rol' => User::ROL_PACIENTE]);
        User::whereKey($registrado->id)->update([
            'created_at' => now()->subDays(400),
            'updated_at' => now()->subDays(400),
        ]);

        $this->artisan('usuarios:purgar-anonimos')->assertSuccessful();

        $this->assertNotSoftDeleted('users', ['id' => $registrado->id]);
    }

    public function test_dry_run_lista_sin_borrar(): void
    {
        $viejo = $this->anonimoDeHace(40);

        $this->artisan('usuarios:purgar-anonimos --dry-run')
            ->expectsOutputToContain("#{$viejo->id}")
            ->assertSuccessful();

        $this->assertNotSoftDeleted('users', ['id' => $viejo->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $viejo->id]);
    }

    public function test_dias_pisa_la_configuracion(): void
    {
        $reciente = $this->anonimoDeHace(5);

        $this->artisan('usuarios:purgar-anonimos --dias=3')->assertSuccessful();

        $this->assertSoftDeleted('users', ['id' => $reciente->id]);
    }

    /**
     * Anonimo creado hace $dias dias con un token; si $tokenUsadoHace no es
     * null, ese token registra uso hace esa cantidad de dias.
     */
    private function anonimoDeHace(int $dias, ?int $tokenUsadoHace = null): User
    {
        $usuario = User::create([
            'name' => User::NOMBRE_TEMPORAL,
            'email' => null,
            'password' => null,
            'rol' => User::ROL_PACIENTE,
        ]);
        Paciente::create(['usuarioId' => $usuario->id]);

        $token = $usuario->createToken('api')->accessToken;

        if ($tokenUsadoHace !== null) {
            $token->forceFill(['last_used_at' => now()->subDays($tokenUsadoHace)])->save();
        }

        // Query builder a proposito: save() pisaria updated_at con "ahora".
        User::whereKey($usuario->id)->update([
            'created_at' => now()->subDays($dias),
            'updated_at' => now()->subDays($dias),
        ]);

        return $usuario->fresh();
    }
}
```

- [ ] **Step 2: Correr el test y verificar que falla**

Run: `php artisan test --filter=PurgarUsuariosAnonimosTest`
Expected: FAIL — `There are no commands defined in the "usuarios" namespace.`

- [ ] **Step 3: Config y `.env.example`**

En `config/auth.php`, antes del `];` final (después de `'password_timeout' => ...`), agregar:

```php
    /*
    |--------------------------------------------------------------------------
    | Pacientes anonimos
    |--------------------------------------------------------------------------
    |
    | Dias sin actividad tras los cuales `usuarios:purgar-anonimos` da de baja
    | a un paciente que entro por POST /auth/anonimo y nunca reclamo su cuenta.
    |
    */

    'anonimos' => [
        'dias_vigencia' => (int) env('ANONIMOS_DIAS_VIGENCIA', 30),
    ],
```

Al final de `.env.example` agregar:

```
# Dias sin actividad tras los cuales se purga a un paciente anonimo que nunca se registro.
ANONIMOS_DIAS_VIGENCIA=30
```

- [ ] **Step 4: Comando**

Crear `app/Console/Commands/PurgarUsuariosAnonimos.php`:

```php
<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Da de baja a los pacientes anonimos que nunca reclamaron su cuenta.
 *
 * POST /auth/anonimo es publico: sin esta purga la tabla users crece sin
 * limite. Solo se toca a quien no mostro actividad en el plazo; un anonimo
 * que sigue usando su token no se pierde. Estudios y archivos no se tocan
 * (evidencia clinica): quedan colgando de un paciente soft-deleted e
 * inaccesibles porque no queda ningun token.
 */
class PurgarUsuariosAnonimos extends Command
{
    protected $signature = 'usuarios:purgar-anonimos
                            {--dias= : Dias sin actividad; por defecto config auth.anonimos.dias_vigencia}
                            {--dry-run : Lista los candidatos sin borrar nada}';

    protected $description = 'Da de baja a los pacientes anonimos sin actividad';

    public function handle(): int
    {
        $dias = (int) ($this->option('dias') ?? config('auth.anonimos.dias_vigencia', 30));
        $limite = now()->subDays($dias);

        $candidatos = User::query()
            ->whereNull('email')
            ->whereNull('auth0Sub')
            ->where('rol', User::ROL_PACIENTE)
            ->where('created_at', '<', $limite)
            ->where('updated_at', '<', $limite)
            // last_used_at lo actualiza Sanctum en cada peticion autenticada:
            // es la senal de actividad real.
            ->whereDoesntHave('tokens', fn (Builder $q) => $q->where('last_used_at', '>=', $limite))
            ->get();

        if ($this->option('dry-run')) {
            $this->info("Candidatos a purga (sin actividad en {$dias} dias): {$candidatos->count()}");
            $candidatos->each(fn (User $usuario) => $this->line("  #{$usuario->id} creado {$usuario->created_at}"));

            return self::SUCCESS;
        }

        foreach ($candidatos as $usuario) {
            DB::transaction(function () use ($usuario) {
                $antes = $usuario->toArray();

                $usuario->tokens()->delete();
                // Soft delete: Paciente y User usan SoftDeletes.
                Paciente::where('usuarioId', $usuario->id)->delete();
                $usuario->delete();

                AuditLog::create([
                    'usuarioId' => null,
                    'entidad' => class_basename($usuario),
                    'entidadId' => $usuario->getKey(),
                    'accion' => 'purgar-anonimo',
                    'antes' => $antes,
                    'despues' => null,
                    'ip' => null,
                ]);
            });
        }

        $this->info("Anonimos purgados: {$candidatos->count()} (sin actividad en {$dias} dias).");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 5: Programar el comando**

Reemplazar el contenido de `routes/console.php` por:

```php
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// La ruta de alta anonima es publica: sin esta purga users crece sin limite.
Schedule::command('usuarios:purgar-anonimos')->daily();
```

- [ ] **Step 6: Correr el test y verificar que pasa**

Run: `php artisan test --filter=PurgarUsuariosAnonimosTest`
Expected: `"result":"passed"`, 6 tests.

- [ ] **Step 7: Verificar el registro del comando y la programación**

Run: `php artisan list usuarios`
Expected: aparece `usuarios:purgar-anonimos`.

Run: `php artisan schedule:list`
Expected: una línea `0 0 * * *  php artisan usuarios:purgar-anonimos`.

- [ ] **Step 8: Commit**

```bash
./vendor/bin/pint --dirty
git add app/Console/Commands/PurgarUsuariosAnonimos.php config/auth.php routes/console.php .env.example tests/Feature/PurgarUsuariosAnonimosTest.php
git commit -m "feat: daily purge of inactive anonymous patients

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 7: Verificación final

**Files:** ninguno nuevo.

- [ ] **Step 1: Suite completa y formato**

Run: `./vendor/bin/pint --test`
Expected: `"result":"passed"` (o sin archivos por corregir).

Run: `php artisan test`
Expected: `"result":"passed"`; el total debe haber crecido en 32 tests respecto al inicio de la rama (3 + 4 + 7 + 9 + 3 + 6).

- [ ] **Step 2: Rutas**

Run: `php artisan route:list --path=auth`
Expected: aparecen `POST api/auth/anonimo`, `POST api/auth/auth0`, `POST api/auth/logout`. Si el comando falla por `TipoEstudioController::listar` (bug preexistente, ver Global Constraints), verificar en su lugar con `grep -n "auth/anonimo" routes/api.php`.

- [ ] **Step 3: Migración contra MySQL de desarrollo (manual, no en CI)**

Run: `php artisan migrate`
Expected: `2026_08_17_100000_make_users_email_nullable ... DONE`. Confirmar en MySQL: `SHOW COLUMNS FROM users LIKE 'email';` → `Null = YES`, y `SHOW INDEX FROM users WHERE Column_name = 'email';` sigue mostrando el índice único.

- [ ] **Step 4: Prueba manual del flujo (opcional, con el servidor levantado)**

```bash
# 1. alta anonima
curl -s -X POST http://localhost:8000/api/auth/anonimo -H "Accept: application/json" | jq
# 2. usar el token
curl -s http://localhost:8000/api/user -H "Accept: application/json" -H "Authorization: Bearer <token>" | jq
# 3. reclamar (accessToken real de Auth0 en el body)
curl -s -X POST http://localhost:8000/api/auth/auth0 -H "Accept: application/json" -H "Authorization: Bearer <token>" \
     -H "Content-Type: application/json" -d '{"accessToken":"<jwt de auth0>"}' | jq
```

Expected: paso 1 devuelve `esTemporal: true`; paso 3 devuelve el mismo `usuario.id` con `esTemporal: false` y `email` cargado.

- [ ] **Step 5: Entregar la rama**

Sin commits pendientes; la rama `feature/paciente-anonimo` lista para PR contra `main`. Cuerpo del PR: enlazar la spec y resumir el contrato para la app (sección "Contrato para la app (Flutter)" de la spec).
