# Tomas diarias y actividad — plan de implementación (backend)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** `paciente_medicamentos` con horarios estructurados, `GET /tomas` que materializa las tomas del día del paciente, y `GET /actividad` con el historial de tomas marcadas y mediciones.

**Architecture:** Laravel 11, `BaseCrudController` con alcance por paciente. Un servicio `App\Support\Tomas` materializa; `TomaController::listar` lo invoca y filtra por día; `ActividadController` mezcla dos consultas y pagina en memoria.

**Tech Stack:** PHP 8.3, Laravel, Sanctum, PHPUnit con sqlite en memoria (`RefreshDatabase`), Carbon.

**Spec:** `docs/superpowers/specs/2026-08-17-tomas-diarias-design.md`

## Global Constraints

- Idioma: español sin tildes en identificadores; commits `feat:`/`test:`/`docs:` en español + `Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>`.
- Tests: `php artisan test --filter=<Clase>` por tarea; `php artisan test` completo antes de cada commit.
- No tocar los cambios locales sin commitear del usuario (`ClinicaController.php`, `PacienteController.php`, plan de paciente anonimo).
- Timezone app: UTC. Fechas de respuesta en UTC ISO 8601.

---

### Task 1: `horarios` en `paciente_medicamentos`

**Files:**
- Create: `database/migrations/2026_08_17_220000_add_horarios_to_paciente_medicamentos.php`
- Modify: `app/Models/PacienteMedicamento.php` (fillable + cast)
- Modify: `app/Http/Controllers/PacienteMedicamentoController.php` (reglas)
- Test: `tests/Feature/PacienteMedicamentoHorariosTest.php`

- [ ] **Test**: crear como doctor con `horarios: ['08:00','13:00']` → 201 y `horarios` array; sin `horarios` → 422; `['8h']` → 422; 7 horarios → 422; duplicados → 422. Setup como `ValidarEstudioTest` (admin, clínica, doctor, paciente en la clínica) + `Medicamento::create(['nombre'=>'Metformina', ...campos requeridos])` — mirar `MedicamentoSeeder` para los campos.
- [ ] **Rojo**: `php artisan test --filter=PacienteMedicamentoHorariosTest`.
- [ ] **Implementar**: migración `$table->json('horarios')->nullable()->after('frecuencia')`; modelo: `'horarios'` en fillable, cast `'horarios' => 'array'`; reglas: `'horarios' => [$req, 'array', 'min:1', 'max:6'], 'horarios.*' => ['required', 'date_format:H:i', 'distinct']`.
- [ ] **Verde + commit**: `feat: horarios estructurados en paciente_medicamentos`.

### Task 2: materialización de tomas y `GET /tomas?dia&zona`

**Files:**
- Create: `database/migrations/2026_08_17_220100_add_unique_to_tomas.php` (único `pacienteMedicamentoId, programadaEn`)
- Create: `app/Support/Tomas.php`
- Modify: `app/Http/Controllers/TomaController.php` (override `listar`)
- Test: `tests/Feature/TomasDiariasTest.php`

- [ ] **Test**: paciente con medicamento activo (`horarios ['08:00','20:00']`, `fechaInicio` ayer) →
  `GET /api/tomas?dia=2026-08-17&zona=America/La_Paz` como el paciente → 200, `total` 2, `programadaEn` de la primera = `2026-08-17T12:00:00.000000Z` (08:00 La Paz = 12:00 UTC), `estado` pendiente, `pacienteMedicamento.medicamento.nombre` presente. Segunda llamada → sigue `total` 2 y `Toma::count()` 2. Medicamento con `activo=false` no genera; `fechaInicio` mañana no genera; `fechaFin` ayer no genera. Paciente B con su propio medicamento: A no ve las de B (`total` 0 para A pidiendo `pacienteId` de B, se ignora) y `POST /tomas/{idDeB}/marcar` como A → 404. Doctor de la clínica con `?pacienteId=A` → materializa y lista. `POST /tomas/{id}/marcar {estado: tomada}` → `tomadaEn` no nulo.
- [ ] **Rojo**.
- [ ] **Implementar**:

```php
// app/Support/Tomas.php
namespace App\Support;

use App\Models\PacienteMedicamento;
use App\Models\Toma;
use Carbon\CarbonImmutable;

class Tomas
{
    /** Crea las tomas pendientes del dia que aun no existan. Idempotente. */
    public static function materializar(int $pacienteId, CarbonImmutable $dia, string $zona): void
    {
        $inicioDia = $dia->setTimezone($zona)->startOfDay();

        $medicamentos = PacienteMedicamento::query()
            ->where('pacienteId', $pacienteId)
            ->where('activo', true)
            ->whereNotNull('horarios')
            ->whereDate('fechaInicio', '<=', $inicioDia->toDateString())
            ->where(fn ($q) => $q->whereNull('fechaFin')->orWhereDate('fechaFin', '>=', $inicioDia->toDateString()))
            ->get();

        foreach ($medicamentos as $pm) {
            foreach ($pm->horarios ?? [] as $hora) {
                [$h, $m] = explode(':', $hora);
                $programadaEn = $inicioDia->setTime((int) $h, (int) $m)->utc();

                Toma::firstOrCreate(
                    ['pacienteMedicamentoId' => $pm->id, 'programadaEn' => $programadaEn],
                    ['estado' => 'pendiente'],
                );
            }
        }
    }
}
```

`TomaController::listar(Request $request)`: `$this->autorizarLectura($request)`; validar `['dia' => ['nullable','date_format:Y-m-d'], 'zona' => ['nullable','timezone:all'], 'pacienteId' => ['nullable','integer']]`; `$zona = $request->query('zona', config('app.timezone'))`; `$dia = CarbonImmutable::parse($request->query('dia', 'today'), $zona)`; resolver `$pacienteId`: si usuario es paciente → `Alcance::pacienteId`; si no y viene `pacienteId` y está en `Alcance::pacientesVisibles` (o admin) → ese; si no → null. Si hay `$pacienteId` → `Tomas::materializar(...)`. Luego construir la consulta como `parent::listar` pero con `whereBetween('programadaEn', [$inicio->utc(), $inicio->endOfDay()->utc()])` — como `listar` del padre no admite hook, copiar su cuerpo mínimo (filtrables, orden, paginación) o refactorizar el padre añadiendo `protected function filtrarListado(Request $request, Builder $consulta): void {}` llamado tras los filtrables y sobreescribirlo aquí (preferible: 4 líneas en el padre, sin duplicar).
- [ ] **Verde + commit**: `feat: GET /tomas materializa las tomas del dia del paciente`.

### Task 3: `GET /actividad`

**Files:**
- Create: `app/Http/Controllers/ActividadController.php`
- Modify: `routes/api.php` (grupo `auth:sanctum`): `Route::get('actividad', [ActividadController::class, 'listar']);`
- Test: `tests/Feature/ActividadTest.php`

- [ ] **Test**: paciente con 1 toma `tomada` (tomadaEn 12:05Z), 1 toma `pendiente`, 1 toma `omitida` (updated_at), 2 mediciones (11:10Z y ayer) → `GET /api/actividad` → `total` 4 (no la pendiente), `data[0].tipo` = 'toma' 12:05Z, orden desc, campos del contrato; `?desde=<hoy>` → 3; `?porPagina=2&pagina=2` → 2 elementos. Doctor con `pacienteId` visible → mismo resultado; paciente ajeno → 200 vacío. Sin sesión → 401.
- [ ] **Rojo**.
- [ ] **Implementar**: resolver `pacienteId` igual que en Task 2 (extraer a `App\Support\Alcance::pacienteObjetivo(User $usuario, ?int $pedido): ?int` y usarlo en ambos); consultar `Toma::with('pacienteMedicamento.medicamento')->whereHas('pacienteMedicamento', fn($q)=>$q->where('pacienteId',$id))->where('estado','!=','pendiente')`, `Medicion::where('pacienteId',$id)`; opcional `desde`: tomas `programadaEn >= desde`, mediciones `medidoEn >= desde`; mapear a arrays `{tipo, en, ...}`; `sortByDesc('en')`; `values()`; paginar con `slice`; responder `{data, pagina, porPagina, total}`.
- [ ] **Verde + commit**: `feat: GET /actividad con tomas marcadas y mediciones del paciente`.

### Task 4: contrato para la app y cierre

- [ ] Escribir `docs/api/tomas-y-actividad.md` con el contrato de la spec (ejemplos reales de las respuestas de los tests).
- [ ] `php artisan test` completo en verde. Commit `docs: contrato de tomas diarias y actividad para la app`.
