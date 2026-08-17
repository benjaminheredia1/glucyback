<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\Alcance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CRUD comun a todas las entidades.
 *
 * Cada consulta pasa por `aplicarAlcance()`, asi que un recurso fuera del alcance
 * del usuario responde 404 y no 403: no se filtra ni siquiera su existencia.
 */
abstract class BaseCrudController extends Controller
{
    /** @var class-string<Model> */
    protected string $modelo;

    /** Relaciones a cargar en listar/ver. @var array<string> */
    protected array $with = [];

    /** @var array<string> */
    protected array $rolesLectura = [User::ROL_ADMIN, User::ROL_DOCTOR, User::ROL_PACIENTE];

    /** @var array<string> */
    protected array $rolesEscritura = [User::ROL_ADMIN];

    /** Columna que ata la fila a un paciente. Null en catalogos. */
    protected ?string $columnaPaciente = null;

    /** Alcance indirecto: ['relacion' => 'chat', 'columna' => 'pacienteId']. @var array<string,string>|null */
    protected ?array $pacienteViaRelacion = null;

    /** Columna que ata la fila a una clinica. */
    protected ?string $columnaClinica = null;

    /** Al crear, un paciente solo puede escribir sobre si mismo. */
    protected bool $forzarPacientePropio = false;

    /** Columnas admitidas como filtro exacto via query string. @var array<string> */
    protected array $filtrables = [];

    /** Columnas admitidas en ?orden=. @var array<string> */
    protected array $ordenables = ['id'];

    protected string $ordenPorDefecto = 'id';

    protected string $direccionPorDefecto = 'desc';

    protected int $porPagina = 25;

    /**
     * @return array<string, array<mixed>>
     */
    abstract protected function reglas(Request $request, bool $creando): array;

    // ---------------------------------------------------------------- acciones

    public function listar(Request $request): JsonResponse
    {
        $this->autorizarLectura($request);

        $consulta = $this->consulta($request);

        foreach ($this->filtrables as $columna) {
            if ($request->filled($columna)) {
                $consulta->where($columna, $request->query($columna));
            }
        }

        if ($request->filled('desde')) {
            $consulta->whereDate('created_at', '>=', $request->query('desde'));
        }

        if ($request->filled('hasta')) {
            $consulta->whereDate('created_at', '<=', $request->query('hasta'));
        }

        $orden = $request->query('orden', $this->ordenPorDefecto);

        if (! in_array($orden, $this->ordenables, true)) {
            $orden = $this->ordenPorDefecto;
        }

        $direccion = strtolower((string) $request->query('direccion', $this->direccionPorDefecto)) === 'asc' ? 'asc' : 'desc';

        $porPagina = min((int) $request->query('porPagina', $this->porPagina), 100);

        return response()->json(
            $consulta->orderBy($orden, $direccion)->paginate(max($porPagina, 1))
        );
    }

    public function ver(Request $request, int $id): JsonResponse
    {
        $this->autorizarLectura($request);

        return response()->json($this->consulta($request)->findOrFail($id));
    }

    public function crear(Request $request): JsonResponse
    {
        $this->autorizarEscritura($request);
        $this->inyectarPacientePropio($request);

        $datos = $request->validate($this->reglas($request, true));
        $datos = $this->antesDeCrear($request, $this->forzarPaciente($request, $datos));

        $registro = DB::transaction(function () use ($request, $datos) {
            $registro = $this->modelo::create($datos);
            $this->verificarAlcance($request, $registro);
            $this->auditar($request, 'crear', $registro, null, $registro->toArray());
            $this->despuesDeCrear($request, $registro);

            return $registro;
        });

        return response()->json($registro->load($this->with), 201);
    }

    public function actualizar(Request $request, int $id): JsonResponse
    {
        $this->autorizarEscritura($request);

        $registro = $this->consulta($request)->findOrFail($id);

        $this->inyectarPacientePropio($request);

        $datos = $request->validate($this->reglas($request, false));
        $datos = $this->antesDeActualizar($request, $registro, $this->forzarPaciente($request, $datos));

        $antes = $registro->toArray();

        DB::transaction(function () use ($request, $registro, $datos, $antes) {
            $registro->update($datos);
            $this->verificarAlcance($request, $registro);
            $this->auditar($request, 'actualizar', $registro, $antes, $registro->toArray());
            $this->despuesDeActualizar($request, $registro);
        });

        return response()->json($registro->fresh($this->with));
    }

    public function eliminar(Request $request, int $id): JsonResponse
    {
        $this->autorizarEscritura($request);

        $registro = $this->consulta($request)->findOrFail($id);

        $this->antesDeEliminar($request, $registro);

        DB::transaction(function () use ($request, $registro) {
            $antes = $registro->toArray();
            // Los modelos clinicos usan SoftDeletes: el dato nunca se pierde.
            $registro->delete();
            $this->auditar($request, 'eliminar', $registro, $antes, null);
        });

        return response()->json(null, 204);
    }

    // ------------------------------------------------------------------ ganchos

    protected function antesDeCrear(Request $request, array $datos): array
    {
        return $datos;
    }

    protected function antesDeActualizar(Request $request, Model $registro, array $datos): array
    {
        return $datos;
    }

    protected function despuesDeCrear(Request $request, Model $registro): void {}

    protected function despuesDeActualizar(Request $request, Model $registro): void {}

    protected function antesDeEliminar(Request $request, Model $registro): void {}

    // -------------------------------------------------------------- internals

    protected function consulta(Request $request): Builder
    {
        $consulta = $this->modelo::query()->with($this->with);

        $this->aplicarAlcance($consulta, $request->user());

        return $consulta;
    }

    /**
     * Una fila creada o modificada tiene que quedar dentro del alcance de quien la
     * escribe. Se comprueba releyendo con el mismo filtro de lectura, asi vale
     * igual para el alcance directo (`pacienteId`) que para el indirecto
     * (`mensajes` -> `chat`, `comprobantes` -> `pago.suscripcion`).
     *
     * Corre dentro de la transaccion: si falla, la escritura se revierte.
     */
    protected function verificarAlcance(Request $request, Model $registro): void
    {
        $usuario = $request->user();

        if ($usuario === null || $usuario->esAdmin()) {
            return;
        }

        $dentro = $this->modelo::query()
            ->tap(fn (Builder $q) => $this->aplicarAlcance($q, $usuario))
            ->whereKey($registro->getKey())
            ->exists();

        abort_unless($dentro, 403, 'El recurso referenciado esta fuera de tu alcance.');
    }

    protected function aplicarAlcance(Builder $consulta, ?User $usuario): void
    {
        if ($usuario === null || $usuario->esAdmin()) {
            return;
        }

        if ($this->columnaClinica !== null) {
            $clinicas = Alcance::clinicasVisibles($usuario);

            if ($clinicas !== null) {
                $consulta->whereIn($this->columnaClinica, $clinicas);
            }
        }

        if ($this->columnaPaciente === null && $this->pacienteViaRelacion === null) {
            return;
        }

        $pacientes = Alcance::pacientesVisibles($usuario);

        if ($pacientes === null) {
            return;
        }

        if ($this->columnaPaciente !== null) {
            $consulta->whereIn($this->columnaPaciente, $pacientes);

            return;
        }

        $relacion = $this->pacienteViaRelacion['relacion'];
        $columna = $this->pacienteViaRelacion['columna'];

        $consulta->whereHas($relacion, fn (Builder $q) => $q->whereIn($columna, $pacientes));
    }

    /**
     * El paciente no manda su propio id: el backend lo resuelve desde la sesion.
     * Se inyecta antes de validar para que `required` se satisfaga y, de paso,
     * cualquier pacienteId que venga en el cuerpo quede pisado.
     */
    protected function inyectarPacientePropio(Request $request): void
    {
        if (! $this->forzarPacientePropio || $this->columnaPaciente === null) {
            return;
        }

        $usuario = $request->user();

        if ($usuario === null || ! $usuario->esPaciente()) {
            return;
        }

        $pacienteId = Alcance::pacienteId($usuario);

        abort_if($pacienteId === null, 422, 'El usuario no tiene perfil de paciente.');

        $request->merge([$this->columnaPaciente => $pacienteId]);
    }

    /**
     * Red de seguridad: aunque la validacion deje pasar otro id, se reescribe.
     */
    protected function forzarPaciente(Request $request, array $datos): array
    {
        if (! $this->forzarPacientePropio || $this->columnaPaciente === null) {
            return $datos;
        }

        $usuario = $request->user();

        if ($usuario === null || ! $usuario->esPaciente()) {
            return $datos;
        }

        $pacienteId = Alcance::pacienteId($usuario);

        abort_if($pacienteId === null, 422, 'El usuario no tiene perfil de paciente.');

        $datos[$this->columnaPaciente] = $pacienteId;

        return $datos;
    }

    protected function autorizarLectura(Request $request): void
    {
        $this->autorizar($request, $this->rolesLectura);
    }

    protected function autorizarEscritura(Request $request): void
    {
        $this->autorizar($request, $this->rolesEscritura);
    }

    private function autorizar(Request $request, array $roles): void
    {
        $usuario = $request->user();

        abort_if($usuario === null, 401, 'No autenticado.');
        abort_unless(in_array($usuario->rol, $roles, true), 403, 'No autorizado para este recurso.');
    }

    protected function auditar(Request $request, string $accion, Model $registro, ?array $antes, ?array $despues): void
    {
        AuditLog::create([
            'usuarioId' => $request->user()?->id,
            'entidad' => class_basename($registro),
            'entidadId' => $registro->getKey(),
            'accion' => $accion,
            'antes' => $antes,
            'despues' => $despues,
            'ip' => $request->ip(),
        ]);
    }
}
