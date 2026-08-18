# API Glucy — tomas diarias y actividad (contrato para la app)

Rama backend: `feature/tomas-diarias`. Base URL: `https://<host>/api`. Todas las rutas exigen `Authorization: Bearer <token>` (vale el token de la identidad anónima) y `Accept: application/json`.

## Qué cambió

- `paciente_medicamentos` tiene **`horarios`**: lista de `"HH:MM"` (1–6). El doctor la manda al asignar un medicamento; con ella el backend genera las **tomas del día**.
- `GET /tomas?dia&zona` **crea** las tomas pendientes del día que falten (idempotente) y las lista. La app no crea tomas a mano.
- `POST /tomas/{id}/marcar` marca `tomada`/`omitida` (ya existía).
- `GET /actividad` (nuevo): historial del paciente = tomas marcadas + mediciones, de la más reciente a la más antigua.

## 1. Asignar medicamento (doctor) — `POST /paciente-medicamentos`

```json
{
  "pacienteId": 4,
  "medicamentoId": 1,
  "dosis": "1 comprimido",
  "frecuencia": "2 veces al día",
  "horarios": ["08:00", "20:00"],
  "fechaInicio": "2026-08-17",
  "fechaFin": null,
  "indicaciones": "Con las comidas"
}
```

- `horarios`: `required|array|min:1|max:6`; cada uno `HH:MM` (24 h), sin repetidos → 422 si falla (`errors.horarios` o `errors.horarios.N`).
- 201 → la fila con `medicamento` (`{id, nombre, concentracion, …}`) y `horarios` como array. `PATCH /paciente-medicamentos/{id}` acepta los mismos campos; `activo: false` desactiva (deja de generar tomas). Catálogo: `GET /medicamentos?porPagina=100`.

## 2. Tomas del día — `GET /tomas`

Query:

| Parámetro | Default | Notas |
|---|---|---|
| `dia` | hoy en `zona` | `YYYY-MM-DD` |
| `zona` | zona de la app (UTC) | IANA, p. ej. `America/La_Paz`. **Mandarla siempre**: define qué es "08:00". Inválida → 422. |
| `pacienteId` | — | Solo doctor/admin. Un paciente siempre ve las suyas (se ignora). |
| `estado`, `pacienteMedicamentoId`, `orden` (`id`\|`programadaEn`), `direccion`, `porPagina` | — | Filtros/orden del CRUD. Recomendado: `orden=programadaEn&direccion=asc`. |

Ejemplo (paciente, La Paz = UTC−4):

```
GET /api/tomas?dia=2026-08-17&zona=America/La_Paz&orden=programadaEn&direccion=asc
```

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 9,
      "pacienteMedicamentoId": 3,
      "programadaEn": "2026-08-17T12:00:00.000000Z",
      "tomadaEn": null,
      "estado": "pendiente",
      "paciente_medicamento": {
        "id": 3, "pacienteId": 4, "dosis": "1 comprimido", "frecuencia": "2 veces al día",
        "horarios": ["08:00", "20:00"], "activo": true,
        "medicamento": {"id": 1, "nombre": "Metformina", "concentracion": "1000 mg"}
      }
    },
    {"id": 10, "programadaEn": "2026-08-18T00:00:00.000000Z", "estado": "pendiente", "...": "..."}
  ],
  "total": 2
}
```

- `programadaEn` y `tomadaEn` van en **UTC**; la app las convierte a local (`08:00` y `20:00` en La Paz).
- La relación llega como **`paciente_medicamento`** (snake_case de Laravel) y dentro `medicamento`.
- Se generan solo para medicamentos `activo = true` con `fechaInicio <= dia <= fechaFin` (o `fechaFin` null). Pedir el mismo día dos veces no duplica. Sin medicamentos con horarios → `data: []`.

## 3. Marcar — `POST /tomas/{id}/marcar`

Body `{"estado": "tomada"}` o `{"estado": "omitida"}`. 200 → la toma con `tomadaEn` (solo si `tomada`) y `paciente_medicamento.medicamento`. Toma de otro paciente → 404.

## 4. Actividad — `GET /actividad`

Query: `desde` (`YYYY-MM-DD`, opcional), `porPagina` (default 30, máx 100), `pagina` (default 1), `pacienteId` (doctor/admin).

```json
{
  "data": [
    {"tipo": "toma", "en": "2026-08-17T12:05:00.000000Z", "tomaId": 9, "estado": "tomada",
     "medicamento": "Metformina", "dosis": "1 comprimido", "programadaEn": "2026-08-17T12:00:00.000000Z"},
    {"tipo": "medicion", "en": "2026-08-17T11:10:00.000000Z", "medicionId": 3,
     "valor": 108, "unidad": "mg/dL", "momento": "ayunas"},
    {"tipo": "toma", "en": "2026-08-16T12:30:00.000000Z", "tomaId": 7, "estado": "omitida",
     "medicamento": "Metformina", "dosis": "1 comprimido", "programadaEn": "2026-08-16T12:00:00.000000Z"}
  ],
  "pagina": 1,
  "porPagina": 30,
  "total": 3
}
```

- Orden: `en` descendente. `en` = `tomadaEn` (tomada), fecha de marcado (omitida) o `medidoEn` (medición). Las tomas `pendiente` no aparecen.
- La app agrupa por día con `en` en hora local.

## 5. Flujo recomendado en la app

1. **Mi plan → Medicamentos**: al abrir (y al volver a primer plano), `GET /tomas?dia=<hoy local>&zona=<zona del teléfono>&orden=programadaEn&direccion=asc`. Cada fila: `medicamento.nombre · dosis`, hora local de `programadaEn`, botón "Marcar" si `pendiente`. "Marcar" → `POST /tomas/{id}/marcar {estado: tomada}` → refrescar lista y actividad. Contador: `tomadas / total`.
2. **Mi plan → Actividad**: `GET /actividad?porPagina=50`; refrescar tras marcar una toma o registrar glucosa.
3. **Editar plan (doctor)**: `GET /medicamentos` para el catálogo; `POST /paciente-medicamentos` con `horarios`; `PATCH …/{id}` para cambiar dosis/horarios; `activo: false` para retirar.
