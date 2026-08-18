# Paciente anónimo: alta sin identidad y reclamo de cuenta

Fecha: 2026-08-17. Estado: aprobado en conversación, pendiente de implementación.

## Problema

El flujo actual exige identidad desde el primer paso: la precalificación pide
`leadEmail` y la cuenta solo nace por Auth0 (`POST /api/auth/auth0`). El
concepto de producto cambió: **hasta la entrega de estudios el paciente no debe
registrarse**. Necesita poder responder la precalificación, subir estudios y
completar parte de su perfil sin correo ni contraseña, y recién después
convertir eso en una cuenta real sin perder nada.

## Decisiones tomadas

| # | Decisión | Por qué |
|---|----------|---------|
| 1 | La credencial del anónimo es un **token Sanctum**, no su `id`. | El `id` es secuencial y enumerable; con él cualquiera subiría o leería estudios clínicos ajenos. Con token, `auth:sanctum`, `Alcance`, `forzarPacientePropio` y la auditoría funcionan sin tocar nada. |
| 2 | El usuario temporal **es la misma fila** de `users`/`pacientes` que la cuenta definitiva. Al reclamar solo se rellenan `email`, `auth0Sub`, `name`, `email_verified_at`. | Cero migración de datos: estudios, archivos y precalificación ya apuntan a ese `pacienteId`/`usuarioId`. Ids estables, auditoría continua. |
| 3 | Si al reclamar el correo de Auth0 ya pertenece a **otra** cuenta → `409`, no se fusiona. | Fusionar dos personas implica reasignar ~15 tablas; riesgo alto. Merge queda para v2. |
| 4 | El anónimo no tiene rutas restringidas: es un `paciente` normal. | `Alcance` ya lo acota a lo propio; la app decide qué pantallas muestra. `User::esTemporal()` deja listo un middleware futuro. |
| 5 | Purga automática de anónimos sin actividad en 30 días (configurable). | La ruta de alta es pública; sin purga la tabla crece sin límite. |
| 6 | "Temporal" se **deriva**: `email IS NULL`. Sin columna nueva. | Al reclamar se escribe el email y la condición desaparece sola; no hay estado que pueda quedar inconsistente. |
| 7 | El token anónimo **no caduca a las 24 h** como el resto (`sanctum.expiration = 1440`). Vive hasta el reclamo o la purga por inactividad. | La app renueva tokens vencidos vía Auth0; el anónimo no tiene Auth0. Con la caducidad global perdería cuenta y estudios al día siguiente. |

## Modelo de datos

- Migración `make_users_email_nullable`: `users.email` pasa a `nullable()`. El
  índice `unique` se conserva: MySQL y SQLite admiten varios `NULL` en un
  unique. `down()` restaura `NOT NULL` con la misma advertencia que la
  migración de `auth0Sub` (falla ruidoso si hay anónimos vivos).
- `users.name` sigue `NOT NULL`: el anónimo nace con `User::NOMBRE_TEMPORAL = 'Paciente'`.
- Sin columnas nuevas. Sin tablas nuevas.

## `User`

- `public const NOMBRE_TEMPORAL = 'Paciente';`
- `public function esTemporal(): bool` → `$this->email === null`. Mismo estilo
  que `esAdmin()` / `esPaciente()`.
- Se expone en el JSON como `esTemporal` (accesor `getEsTemporalAttribute()`
  + `$appends = ['esTemporal']`) para que la app sepa cuándo pedir registro
  sin inferirlo de `email == null`.

## Endpoint 1: alta anónima

```
POST /api/auth/anonimo            público, throttle:5,1
body: { "dispositivo"?: string(max 100) }
201 → { "token": "...", "usuario": { id, name, email: null, rol: "paciente", esTemporal: true, paciente: {...}, doctor: null } }
```

Controlador nuevo `App\Http\Controllers\Auth\SesionAnonimaController::store`.
Dentro de una transacción:

1. `User::create(['name' => User::NOMBRE_TEMPORAL, 'email' => null, 'auth0Sub' => null, 'password' => null, 'rol' => User::ROL_PACIENTE])`.
2. `Paciente::create(['usuarioId' => $usuario->id])` (mismo paciente mínimo que crea hoy `Auth0SessionController`).
3. `AuditLog` con `usuarioId = id del nuevo usuario`, `entidad 'User'`, `accion 'crear-anonimo'`, `ip`.

Fuera de la transacción: `createToken($dispositivo ?? 'api')`. Respuesta con el
mismo shape que `/auth/auth0` (para que la app reutilice el parser), código 201.

### Vida del token anónimo

`config/sanctum.php` vence todo token a los 1440 minutos y la app lo renueva
en silencio vía Auth0 ante un 401. El anónimo no puede renovar: sin excepción,
a las 24 h perdería el acceso a su cuenta y a lo que subió.

En `AppServiceProvider::boot`, `Sanctum::authenticateAccessTokensUsing(...)`:
si el `tokenable` es un `User` con `esTemporal()`, el token vale mientras no
tenga `expires_at` vencido (no se le crea con `expires_at`, así que vale
indefinidamente); para cualquier otro usuario se respeta la validez que ya
calculó Sanctum (`$esValido`). El token anónimo muere por dos vías: el reclamo
(se revocan todos) o la purga por inactividad (borra tokens y da de baja al
usuario; un `User` soft-deleted ya no resuelve como `tokenable`).

Compromiso asumido: es un bearer de larga vida en el dispositivo. Es la única
credencial posible sin identidad, y la purga a 30 días de inactividad actúa
como caducidad deslizante.

## Endpoint 2: reclamo (en `POST /api/auth/auth0`)

La ruta sigue pública. Cambio: ahora **lee la sesión Sanctum si viene**.

Helper nuevo `App\Support\SesionOpcional::usuario(Request $request): ?User`:

- `$request->user('sanctum')` resuelve un `User` → ese usuario. (Funciona sin
  middleware `auth:sanctum`: el guard resuelve el Bearer al pedirlo. También
  cubre `Sanctum::actingAs()` en tests.)
- No resuelve y **hay** `Authorization: Bearer` (token inválido, revocado,
  purgado) → `abort(401, 'El token de la sesion no es valido.')`. Nunca se
  ignora en silencio: si la app cree que reclama y no reclama, el paciente
  pierde sus estudios sin enterarse.
- No resuelve y no hay Bearer → `null`.

En `Auth0SessionController::store`:

```
$sesion  = SesionOpcional::usuario($request);
$anonimo = $sesion?->esTemporal() ? $sesion : null;
```

Un Bearer de una cuenta ya real (`esTemporal() === false`) se ignora: el
comportamiento es el re-login de hoy, sin reclamo.

`resolverUsuario(Request, PerfilAuth0, ?User $anonimo)`. Si `$anonimo !== null`,
en este orden (todo dentro de la transacción existente):

1. `abort_unless($perfil->emailVerificado, 422, 'Auth0 no ha verificado este correo para reclamar la cuenta.')`.
   Va primero: un correo sin verificar no debe poder sondear si existe una cuenta.
2. Cuenta dada de baja con ese email o sub (`onlyTrashed`) → `403`, mismo mensaje que hoy.
3. Existe otro `User` con ese `auth0Sub` **o** ese `email` → `abort(409, 'Ya existe una cuenta con este correo. Inicia sesion con ella.')`. El anónimo queda intacto.
4. Reclamo:
   ```
   $anonimo->update([
       'email'             => $email,               // ya normalizado (trim + minúsculas)
       'auth0Sub'          => $perfil->sub,
       'name'              => $anonimo->name === User::NOMBRE_TEMPORAL ? ($perfil->nombre ?? $email) : $anonimo->name,
       'email_verified_at' => now(),
   ]);
   ```
   `name` solo se pisa si sigue siendo el placeholder: el anónimo pudo haber
   completado su nombre por `PATCH /perfil` antes de registrarse.
5. `AuditLog` `accion 'reclamar'` con `antes`/`despues`.
6. No se crea `Paciente`: ya existe.

De vuelta en `store`, si hubo reclamo se revocan **todos** los tokens del
usuario (`$usuario->tokens()->delete()`) antes de emitir el nuevo: el reclamo
cierra la sesión anónima y la app guarda el token nuevo de la respuesta. Sin
reclamo, se conserva la lógica actual (borrar solo el token del mismo
`dispositivo`).

Respuesta: sin cambios (`200 { token, usuario }`).

## Precalificación sin correo

`PrecalificacionController::evaluar` (sigue pública, `throttle:10,1`):

- `$sesion = SesionOpcional::usuario($request)` (mismas reglas: Bearer inválido → 401).
- Si `$sesion !== null && $sesion->esPaciente()`: `pacienteId = Alcance::pacienteId($sesion)`
  (`abort 422` si no tiene perfil de paciente, mensaje ya existente) y **se
  ignora** cualquier `pacienteId` del cuerpo.
- Sin sesión: comportamiento actual (`pacienteId` / `leadEmail` del cuerpo).

`leadEmail` y `POST /precalificaciones/{id}/vincular` se conservan por
compatibilidad; el flujo nuevo no los necesita.

## Purga de anónimos abandonados

Comando `App\Console\Commands\PurgarUsuariosAnonimos`, firma
`usuarios:purgar-anonimos {--dias=} {--dry-run}`. Programado `daily()` en
`routes/console.php`.

Config: `config/auth.php` → `'anonimos' => ['dias_vigencia' => env('ANONIMOS_DIAS_VIGENCIA', 30)]`.
`--dias` pisa el config.

Candidato a purga = `User` que cumple todo:

- `email IS NULL` y `auth0Sub IS NULL` y `rol = paciente`
- `created_at < ahora - dias` y `updated_at < ahora - dias`
- ningún token con `last_used_at >= ahora - dias` (`whereDoesntHave('tokens', ...)`)

Por cada candidato, en transacción: `tokens()->delete()`, soft delete de
`Paciente`, soft delete de `User`, `AuditLog` con `usuarioId = null` (acción
del sistema, sin actor), `entidad 'User'`, `accion 'purgar-anonimo'`.

`--dry-run` lista los ids y no toca nada. Salida: cantidad purgada.

Estudios y archivos del purgado no se tocan (siguen apuntando a un paciente
soft-deleted, inaccesibles porque no queda token). Borrado físico de archivos:
fuera de alcance, decisión aparte por ser evidencia clínica.

## Alcance y permisos

Sin cambios en `Alcance` ni `BaseCrudController`. El anónimo es
`rol = paciente` con fila en `pacientes` → ve y escribe solo lo propio,
`clinicaId = null` → ningún doctor lo ve hasta que se le asigne clínica (igual
que hoy con los pacientes creados por Auth0; fuera de alcance).

## Contrato para la app (Flutter)

1. Al abrir sin token guardado: `POST /api/auth/anonimo` → guardar `token`.
2. Mandar `Authorization: Bearer <token>` en todo, como hoy: `/precalificacion/evaluar`,
   `/archivos/subir`, `/estudios-medicos`, `PATCH /perfil`, `GET /user`.
3. `GET /user` → `esTemporal: true` ⇒ mostrar el CTA de registro cuando el flujo lo pida.
4. Registro: login en Auth0 → `POST /api/auth/auth0 { accessToken, dispositivo? }`
   **con el Bearer anónimo** → reemplazar el token guardado por el de la respuesta.
   - `409`: ya existe cuenta con ese correo. Ofrecer "iniciar sesión" repitiendo
     `POST /auth/auth0` **sin** Bearer; avisar que lo cargado como anónimo no se
     transfiere.
   - `401`: el token anónimo ya no vale (purgado). Borrarlo y volver al paso 1.
   - `422`: correo sin verificar en Auth0.
5. Cualquier `401` en la API con token guardado ⇒ borrar token, volver al paso 1.

## Pruebas (Feature, sqlite en memoria como el resto)

`tests/Feature/Auth/SesionAnonimaTest.php`
- alta devuelve 201, `token`, `usuario.esTemporal = true`, `usuario.email = null`; existe fila en `pacientes`; `audit_logs` con `crear-anonimo`.
- el token sirve: `GET /api/user` → 200 con el mismo id.
- el token anónimo sigue valiendo pasados 2 días (`travel`); el de una cuenta real con correo vence a las 24 h como hasta ahora (regresión).
- el anónimo sube archivo (`/archivos/subir`) y crea estudio (`/estudios-medicos`) → 201 con **su** `pacienteId` aunque mande otro.
- `GET /api/pacientes` como anónimo devuelve solo el propio.

`tests/Feature/Auth/ReclamoCuentaAnonimaTest.php`
- reclamo feliz: mismo `users.id`, `email`/`auth0Sub` escritos, `esTemporal = false`, `email_verified_at` no nulo; el estudio subido antes sigue visible con el token nuevo; el token anónimo viejo responde 401; no se creó un segundo `Paciente`; `audit_logs` con `reclamar`.
- `name`: placeholder se reemplaza por el nombre de Auth0; nombre editado por `PATCH /perfil` se conserva.
- conflicto: ya existe cuenta con ese email → 409; el anónimo sigue con `email = null` y sus estudios; no se creó usuario nuevo.
- conflicto por `auth0Sub` existente → 409.
- correo sin verificar → 422 y anónimo intacto.
- cuenta dada de baja con ese email → 403.
- Bearer inválido → 401, no se crea nada.
- Bearer de cuenta real + Auth0 del mismo usuario → 200 re-login, sin cambios (regresión).
- sin Bearer → todo `Auth0LoginTest` sigue verde (regresión).

`tests/Feature/PrecalificacionEvaluarAnonimoTest.php`
- con Bearer anónimo, `evaluar` guarda `pacienteId` propio e ignora el del cuerpo.
- sin Bearer, comportamiento actual.
- Bearer inválido → 401.

`tests/Feature/PurgarUsuariosAnonimosTest.php`
- purga anónimo viejo sin actividad (user y paciente soft-deleted, tokens borrados, audit).
- conserva anónimo viejo con token usado hace poco.
- conserva anónimo reciente.
- nunca toca usuarios con email (aunque sean viejos).
- `--dry-run` no borra; `--dias` pisa el config.

## Fuera de alcance (v2)

- Merge de datos cuando el correo ya tiene cuenta.
- Restricción de rutas al anónimo (p. ej. bloquear suscripciones).
- Borrado físico de archivos de anónimos purgados.
- Captcha / honeypot en `/auth/anonimo`.
- Asignación automática de clínica.

## Archivos a tocar

- `database/migrations/2026_08_17_100000_make_users_email_nullable.php` (nuevo)
- `app/Models/User.php`
- `app/Support/SesionOpcional.php` (nuevo)
- `app/Http/Controllers/Auth/SesionAnonimaController.php` (nuevo)
- `app/Providers/AppServiceProvider.php` (callback de validez de tokens)
- `app/Http/Controllers/Auth/Auth0SessionController.php`
- `app/Http/Controllers/PrecalificacionController.php`
- `app/Console/Commands/PurgarUsuariosAnonimos.php` (nuevo)
- `config/auth.php`, `.env.example`
- `routes/api.php`, `routes/console.php`
- `tests/Feature/Auth/SesionAnonimaTest.php`, `tests/Feature/Auth/ReclamoCuentaAnonimaTest.php`,
  `tests/Feature/PrecalificacionEvaluarAnonimoTest.php`, `tests/Feature/PurgarUsuariosAnonimosTest.php` (nuevos)
