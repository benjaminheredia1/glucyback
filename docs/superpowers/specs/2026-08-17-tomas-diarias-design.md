# Tomas diarias y actividad del paciente — diseño (backend)

Fecha: 2026-08-17. Rama: `feature/tomas-diarias`. Consumidor: app Flutter (`glucy_app`), pantalla "Mi plan" (Medicamentos / Actividad) y "Editar plan" del doctor.

## Problema

`paciente_medicamentos.frecuencia` es texto libre y nada genera filas en `tomas`: la app no puede listar "las tomas de hoy" ni registrar que se tomó una. Tampoco hay un historial unificado (tomas + mediciones) para la pestaña Actividad.

## Decisiones

- **A. Horarios estructurados.** `paciente_medicamentos.horarios`: JSON, lista de `"HH:MM"` (1–6, sin duplicados, orden libre). `frecuencia` se conserva como texto descriptivo.
- **B. Materialización al consultar.** `GET /tomas?dia=YYYY-MM-DD&zona=<IANA>` crea, antes de listar, las tomas `pendiente` que falten para ese día (`firstOrCreate` por `pacienteMedicamentoId + programadaEn`; índice único). Sin job nocturno. Idempotente.
- **C. Zona horaria del paciente.** `zona` (IANA, p. ej. `America/La_Paz`) la manda la app; por defecto `config('app.timezone')` (UTC). `programadaEn` se guarda en UTC = `dia HH:MM` en `zona`. El filtro por `dia` usa el mismo rango. Se valida con `timezone:all`.
- **D. Alcance.** Paciente: solo sus tomas (ya cubierto por `pacienteViaRelacion`). Doctor/admin: puede pasar `pacienteId` para materializar/listar las de un paciente visible; sin `pacienteId` no materializa, solo lista.
- **E. Actividad.** `GET /actividad`: historial del paciente autenticado (o `pacienteId` visible para doctor/admin), mezcla de tomas con `estado != pendiente` y mediciones, orden desc por fecha, paginado en memoria (`porPagina` ≤ 100, `pagina`), filtro `desde` (fecha).

## Contrato

### `PATCH|POST /paciente-medicamentos` (existente) — nuevo campo
- `horarios`: `["08:00","13:00"]` requerido al crear (`required|array|min:1|max:6`, cada uno `date_format:H:i`, `distinct`). Se devuelve como array.

### `GET /tomas` (existente, ampliado)
- Query: `dia` (`Y-m-d`, default hoy en `zona`), `zona` (IANA, default app), `pacienteId` (solo doctor/admin), más los filtros/paginación de siempre (`estado`, `pacienteMedicamentoId`, `porPagina`, `orden`).
- Efecto: materializa las tomas del día para el paciente resuelto (propio o `pacienteId`) a partir de sus `paciente_medicamentos` con `activo = true` y `fechaInicio <= dia <= fechaFin|null`.
- Respuesta: paginación Laravel de tomas con `pacienteMedicamento.medicamento`. `programadaEn`/`tomadaEn` en UTC ISO.

### `POST /tomas/{id}/marcar` (existente)
- Body `{estado: 'tomada'|'omitida'}`. Paciente solo las suyas (404 si ajena, por alcance).

### `GET /actividad` (nuevo)
- Query: `desde` (`Y-m-d`, opcional), `pacienteId` (doctor/admin), `porPagina` (default 30, max 100), `pagina`.
- 200:
```json
{"data":[
  {"tipo":"toma","en":"2026-08-17T12:05:00.000000Z","tomaId":9,"estado":"tomada","medicamento":"Metformina","dosis":"1000 mg","programadaEn":"2026-08-17T12:00:00.000000Z"},
  {"tipo":"medicion","en":"2026-08-17T11:10:00.000000Z","medicionId":3,"valor":108,"unidad":"mg/dL","momento":"ayunas"}
],"pagina":1,"porPagina":30,"total":2}
```
- `en` = `tomadaEn ?? updated_at` para tomas omitidas; `medidoEn` para mediciones.

## Componentes

- Migración: `horarios` JSON nullable en `paciente_medicamentos` (cast `array`); índice único `tomas(pacienteMedicamentoId, programadaEn)`.
- `PacienteMedicamentoController::reglas`: `horarios`.
- `App\Support\Tomas` (servicio): `materializar(int $pacienteId, CarbonImmutable $dia, string $zona): void`.
- `TomaController::listar` (override): resuelve paciente/día/zona, llama a `Tomas::materializar`, aplica `whereBetween programadaEn` del día y delega en `parent::listar`.
- `ActividadController::listar`.
- Ruta `GET /actividad` en el grupo `auth:sanctum`.

## Tests (Feature, sqlite en memoria)

- `PacienteMedicamentoHorariosTest`: crea con horarios válidos (201, array); rechaza vacío, >6, formato malo, duplicados (422).
- `TomasDiariasTest`: paciente con 2 horarios → `GET /tomas?dia&zona` crea 2 pendientes con `programadaEn` UTC correcto; segunda llamada no duplica; medicamento inactivo o fuera de rango de fechas no genera; paciente A no ve ni marca las de B (404); doctor con `pacienteId` visible sí; `marcar` tomada fija `tomadaEn`.
- `ActividadTest`: mezcla tomas marcadas y mediciones, orden desc, excluye pendientes, `desde` filtra, paginación.

## Fuera de alcance

- Notificaciones/recordatorios de toma.
- Reprogramar tomas pasadas; editar horarios no borra tomas ya creadas (las futuras se generan con el horario nuevo).
- Panel web.
