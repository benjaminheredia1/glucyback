<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Paciente;
use App\Models\User;
use App\Support\Auth0\Auth0NoDisponible;
use App\Support\Auth0\PerfilAuth0;
use App\Support\Auth0\TokenAuth0Invalido;
use App\Support\Auth0\VerificadorAuth0;
use App\Support\SesionOpcional;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use OpenApi\Attributes as OA;

/**
 * Canjea un access token de Auth0 por uno de Sanctum.
 *
 * El resto de la API sigue autenticando con Sanctum: asi el alcance, los roles y
 * la auditoria no cambian por haber movido la identidad a Auth0.
 *
 * Si la peticion trae el Bearer de un paciente anonimo (POST /auth/anonimo),
 * el intercambio ademas reclama esa cuenta: escribe email y auth0Sub sobre la
 * misma fila en vez de crear un usuario nuevo.
 */
class Auth0SessionController extends Controller
{
    public function __construct(private readonly VerificadorAuth0 $verificador) {}

    #[OA\Post(
        path: '/auth/auth0',
        tags: ['Auth'],
        summary: 'Canjear access token de Auth0 por token de Sanctum',
        description: 'Login de la app movil. Si la peticion trae ademas el Bearer de un paciente anonimo (POST /auth/anonimo), la cuenta anonima se reclama: queda con el correo y auth0Sub de Auth0 y se revocan sus tokens previos. Limite: 10 por minuto.',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['accessToken'],
            properties: [
                new OA\Property(property: 'accessToken', type: 'string', description: 'Access token emitido por Auth0 (PKCE)'),
                new OA\Property(property: 'dispositivo', type: 'string', maxLength: 100, description: 'Nombre del token; un token por dispositivo'),
            ],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Sesion iniciada', content: new OA\JsonContent(ref: '#/components/schemas/SesionIniciada')),
            new OA\Response(response: 401, description: 'Access token de Auth0 invalido', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
            new OA\Response(response: 503, description: 'Auth0 no disponible', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
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

    #[OA\Post(
        path: '/auth/logout',
        tags: ['Auth'],
        summary: 'Cerrar sesion',
        description: 'Revoca el token con el que se hizo la peticion.',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 204, description: 'Sesion cerrada'),
            new OA\Response(response: 401, ref: '#/components/responses/NoAutenticado'),
        ],
    )]
    public function destroy(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json(null, 204);
    }

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

        if ($porSub !== null) {
            return $porSub;
        }

        $porEmail = User::where('email', $email)->first();

        if ($porEmail !== null) {
            // Vincular una identidad nueva a una cuenta existente solo es seguro
            // si Auth0 ya comprobo el correo. Sin eso, cualquiera que declare un
            // correo ajeno se apropiaria de la cuenta.
            abort_unless($perfil->emailVerificado, 422, 'Auth0 no ha verificado este correo.');

            $antes = $porEmail->toArray();

            $porEmail->update(['auth0Sub' => $perfil->sub]);

            $this->auditar($request, 'vincular', $porEmail, $antes, $porEmail->toArray());

            return $porEmail;
        }

        // Una cuenta dada de baja no revive por un login: si se borro fue una
        // decision humana (p. ej. una cuenta clinica suspendida) y solo otro
        // humano deberia reactivarla, no un intercambio de token automatico.
        // El correo en Auth0 puede haber cambiado desde la baja, asi que el sub
        // tambien cuenta como coincidencia: sin esto, una cuenta borrada cuyo
        // sub ya conocemos cae en el User::create() de abajo y explota contra
        // el indice unico de auth0Sub (500) en vez de dar el 403 que toca aqui.
        // El where() anidado es a proposito: un orWhere() suelto aqui rompe la
        // precedencia y deja pasar auth0Sub sin exigir tambien deleted_at.
        abort_if(
            User::onlyTrashed()->where(function ($consulta) use ($email, $perfil) {
                $consulta->where('email', $email)->orWhere('auth0Sub', $perfil->sub);
            })->exists(),
            403,
            'Esta cuenta esta dada de baja. Contacta con soporte.'
        );

        // El alta nueva solo es segura si Auth0 ya verifico el correo. Sin esto,
        // cualquiera podria declarar un correo ajeno en Auth0, conseguir que se
        // le cree una cuenta de paciente para ese correo y luego usarla en
        // vincular() (PrecalificacionController) para leer el cuestionario
        // clinico anonimo de la victima via su leadEmail.
        abort_unless($perfil->emailVerificado, 422, 'Auth0 no ha verificado este correo para crear una cuenta.');

        // El alta por Auth0 solo crea pacientes. Doctor y admin los promueve el
        // panel de administracion.
        $usuario = User::create([
            'name' => $perfil->nombre ?? $perfil->email,
            'email' => $email,
            'auth0Sub' => $perfil->sub,
            'rol' => User::ROL_PACIENTE,
            'password' => null,
            'email_verified_at' => now(),
        ]);

        // Paciente minimo: solo usuarioId. fechaNacimiento, tipoDiabetes y el
        // resto los completa el onboarding despues (Auth0 no tiene forma de
        // aportarlos en el signup). Sin esta fila, vincular() no encuentra
        // Alcance::pacienteId() y aborta 422 en el mismo flujo que existe para
        // reclamar el cuestionario clinico anonimo.
        Paciente::create(['usuarioId' => $usuario->id]);

        $this->auditar($request, 'crear', $usuario, null, $usuario->toArray());

        return $usuario;
    }

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

    /**
     * Mismo formato que BaseCrudController::auditar(), pero usuarioId es la
     * cuenta afectada y no $request->user(): aqui todavia no hay sesion de
     * Sanctum, el propio intercambio de token es lo que la crea.
     */
    private function auditar(Request $request, string $accion, User $usuario, ?array $antes, ?array $despues): void
    {
        AuditLog::create([
            'usuarioId' => $usuario->id,
            'entidad' => class_basename($usuario),
            'entidadId' => $usuario->getKey(),
            'accion' => $accion,
            'antes' => $antes,
            'despues' => $despues,
            'ip' => $request->ip(),
        ]);
    }
}
