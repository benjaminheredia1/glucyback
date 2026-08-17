# API Glucy — registro diferido del paciente (contrato para la app)

Rama backend: `feature/paciente-anonimo`. Base URL: `https://<host>/api`.
Headers en toda petición: `Accept: application/json`; `Authorization: Bearer <token>` cuando haya token.
Todas las respuestas son JSON. Errores: `{"message": "..."}` (+ `"errors": {...}` en 422 de validación).

## Qué cambió

- **Antes:** la primera pantalla pedía correo (`leadEmail`) y la cuenta nacía recién en el login con Auth0.
- **Ahora:** la app crea una **identidad temporal sin datos** (`POST /auth/anonimo`), guarda el token y con él el paciente hace todo el onboarding (precalificación, subida de estudios, perfil). Cuando el flujo lo pida (después de entregar estudios), el paciente se registra con Auth0 y la app llama `POST /auth/auth0` **mandando el mismo Bearer**: la cuenta anónima se convierte en la real. **Mismo `usuario.id`, mismo `paciente.id`, mismos estudios**; no se migra nada.
- El usuario trae un campo nuevo `esTemporal` (bool) en todo JSON de usuario. `email` es `null` mientras sea temporal.

## Endpoints

### 1. `POST /api/auth/anonimo` — alta anónima (nuevo, público)

- Rate limit: 5 por minuto por IP (429 si se pasa).
- Body opcional: `{"dispositivo": "android-pixel-7"}` (string, máx. 100). Mismo campo que en `/auth/auth0`; nombra el token.
- **201**:

```json
{
  "token": "1|yLKGYVacQ1iDO79ddBrxWsxrJzWRNtB5oO0Uteec954bce62",
  "usuario": {
    "id": 1,
    "name": "Paciente",
    "email": null,
    "auth0Sub": null,
    "rol": "paciente",
    "esTemporal": true,
    "created_at": "2026-08-17T20:41:46.000000Z",
    "updated_at": "2026-08-17T20:41:46.000000Z",
    "doctor": null,
    "paciente": {
      "id": 1,
      "usuarioId": 1,
      "clinicaId": null,
      "fechaNacimiento": null,
      "sexo": null,
      "tipoDiabetes": null,
      "pesoKg": null,
      "tallaCm": null,
      "diagnosticadoEn": null,
      "alergias": null,
      "comorbilidades": null,
      "tabaquismo": false,
      "contactoEmergencia": null,
      "created_at": "2026-08-17T20:41:46.000000Z",
      "updated_at": "2026-08-17T20:41:46.000000Z",
      "deleted_at": null
    }
  }
}
```

- **422** (validación): `{"message": "The dispositivo field must not be greater than 100 characters.", "errors": {"dispositivo": ["..."]}}`
- Guardar `token` en el **mismo storage seguro** donde hoy se guarda el token de Auth0. Es la única credencial: no usar `usuario.id` como identificador de sesión.

### 2. `GET /api/user` — usuario actual (existente; ahora con `esTemporal`)

- Bearer obligatorio. **200**:

```json
{
  "id": 1,
  "name": "Paciente",
  "apellidoPaterno": null,
  "apellidoMaterno": null,
  "email": null,
  "email_verified_at": null,
  "telefono": null,
  "auth0Sub": null,
  "rol": "paciente",
  "esTemporal": true,
  "created_at": "2026-08-17T20:41:46.000000Z",
  "updated_at": "2026-08-17T20:41:46.000000Z",
  "deleted_at": null,
  "doctor": null,
  "paciente": { "...mismo objeto que arriba..." }
}
```

- `esTemporal: true` ⇒ la cuenta todavía no está registrada: mostrar el CTA de registro cuando el flujo lo pida.
- **401** `{"message": "Unauthenticated."}` ⇒ el token no vale (ver "Vida de los tokens").

### 3. Onboarding con el token anónimo (rutas existentes, mismo contrato)

El anónimo es un paciente normal: cualquier ruta de paciente funciona con su Bearer y solo ve lo suyo.

| Ruta | Notas |
|------|-------|
| `GET /api/precalificacion/preguntas` | Pública. Lista de preguntas activas `{id, codigo, texto, orden, version}`. |
| `POST /api/precalificacion/evaluar` | Pública. **Con Bearer**, el backend ata la precalificación al paciente de la sesión e **ignora** `pacienteId` del cuerpo; **ya no hace falta `leadEmail`**. Body: `{"respuestas": [{"preguntaId": 1, "respuesta": "si"}, ...]}` con **todas** las preguntas activas (`"si"`/`"no"`). 201 → precalificación con `pacienteId` propio, `resultado` (`apto`/`no_apto`/`urgente`), `motivo`. Bearer inválido → 401. |
| `POST /api/archivos/subir` | multipart: `archivo` (pdf/jpg/png), opcional `nombre`, `descripcion`. 201 → `{id, nombre, mime, sizeBytes, ...}`. |
| `POST /api/estudios-medicos` | `{"tipoEstudioId": 1, "fecha": "2026-08-17", "archivoId": 5, "descripcion"?: "...", "valor"?: 6.5, "unidad"?: "%"}`. `pacienteId` lo pone el backend (si se manda, se ignora). 201. |
| `GET /api/estudios-medicos`, `GET /api/estudios-medicos/{id}` | Solo los propios. |
| `PATCH /api/perfil` | `{name?, apellidoPaterno?, apellidoMaterno?, telefono?, fechaNacimiento?, sexo? ("femenino"/"masculino"/"otro"), pesoKg?, tallaCm?}` → 200 usuario. **No acepta `email`**: la identidad la aporta Auth0 al reclamar. |

`POST /precalificaciones/{id}/vincular` (reclamo por `leadEmail`) sigue existiendo pero **el flujo nuevo no lo necesita**.

### 4. `POST /api/auth/auth0` — login/alta por Auth0 (existente; ahora **reclama** la cuenta si viene el Bearer anónimo)

- Body: `{"accessToken": "<access token de Auth0>", "dispositivo"?: "android-pixel-7"}`.
- **Con** `Authorization: Bearer <token anónimo>` ⇒ modo reclamo. **Sin** header ⇒ login/alta de siempre. Con Bearer de una cuenta ya real ⇒ re-login normal, sin reclamo.

Respuestas en modo reclamo:

| Código | `message` | Qué pasó / qué hacer |
|--------|-----------|----------------------|
| **200** | — | `{token, usuario}`. `usuario.id` **es el mismo** que el anónimo; `email`, `auth0Sub`, `email_verified_at` cargados; `esTemporal: false`. **El token anónimo queda revocado**: reemplazarlo por el nuevo. `name`: si el usuario lo editó por `/perfil` se conserva; si seguía `"Paciente"` se toma el nombre de Auth0. |
| **409** | `Ya existe una cuenta con este correo. Inicia sesion con ella.` | El correo o la identidad de Auth0 ya pertenece a **otra** cuenta. La cuenta anónima **no se toca** y su token sigue válido. UX: ofrecer "Iniciar sesión con esa cuenta" → repetir `POST /auth/auth0` con el mismo `accessToken` pero **sin** `Authorization` → 200 con la cuenta vieja; avisar que lo cargado como anónimo **no se transfiere**. Alternativa: registrarse con otro correo. |
| **401** | `El token de la sesion no es valido.` | El Bearer anónimo ya no vale (purgado o revocado). Borrar el token local; si el usuario nunca se registró, crear otra identidad (`/auth/anonimo`); si ya se había registrado, loguear sin Bearer. |
| **401** | `Access token de Auth0 invalido.` | El `accessToken` de Auth0 no valida. Reintentar el login en Auth0. |
| **422** | `Auth0 no ha verificado este correo para reclamar la cuenta.` | Pedir al usuario que verifique el correo en Auth0 y reintentar. La cuenta anónima sigue viva. |
| **422** | `Auth0 no entrego un correo para esta cuenta.` | La conexión de Auth0 no aportó email. |
| **403** | `Esta cuenta esta dada de baja. Contacta con soporte.` | Ese correo/identidad está dado de baja. |
| **503** | `El proveedor de identidad no esta disponible.` | Auth0 caído; reintentar más tarde. |

### 5. Vida de los tokens

- **Token anónimo: no caduca por tiempo.** Muere solo por (a) reclamo → usar el token nuevo de la respuesta, o (b) **purga por 30 días sin actividad** (cualquier petición autenticada cuenta como actividad). Después de la purga cualquier ruta responde `401 Unauthenticated.` ⇒ borrar el token local y volver a crear identidad anónima (lo anterior se perdió).
- **Token de cuenta real** (después de reclamar o de un login): caduca a las **24 h** como hasta ahora ⇒ renovar vía Auth0 (`POST /auth/auth0` sin Bearer, o con el Bearer vencido: da igual, se ignora).
- `dispositivo`: un token por dispositivo; re-loguear desde el mismo dispositivo reemplaza el anterior. En el reclamo se revocan **todos** los tokens del anónimo.

### 6. Flujo recomendado

1. **Arranque.** ¿Hay token guardado?
   - No → `POST /auth/anonimo` → guardar `token`; estado `esTemporal = true`.
   - Sí → `GET /user`. 200 → leer `esTemporal`. 401 → borrar token; si el usuario ya se había registrado, ofrecer login Auth0 sin Bearer; si no, volver a `POST /auth/anonimo`.
2. **Onboarding con Bearer** (anónimo): precalificación → subida de estudios → perfil. Todo con `Authorization: Bearer <token>`.
3. **Registro** (cuando `esTemporal` es `true` y el flujo lo pide, p. ej. tras entregar estudios): login Auth0 → `POST /auth/auth0 {accessToken}` **con** Bearer anónimo → manejar 200 / 409 / 401 / 422 según la tabla.
4. **Cuenta real:** igual que hoy; ante 401 renovar por Auth0.
5. **Cualquier 401 `Unauthenticated.`** en una ruta autenticada = token inválido o vencido: aplicar el paso 1.

### 7. Notas

- Rate limits: `/auth/anonimo` 5/min, `/auth/auth0` 10/min, `/precalificacion/evaluar` 10/min por IP → 429.
- En producción los errores no traen `exception`/`trace`, solo `message` (+ `errors` en validación).
- Los estudios de un anónimo no son visibles para ningún doctor hasta que un admin le asigne clínica (igual que hoy con las cuentas creadas por Auth0); no depende de la app.
