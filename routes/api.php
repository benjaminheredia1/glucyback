<?php

use App\Http\Controllers\ActividadController;
use App\Http\Controllers\AlertaController;
use App\Http\Controllers\AntecedenteFamiliarController;
use App\Http\Controllers\ArchivoController;
use App\Http\Controllers\ArticuloAyudaController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\Auth0SessionController;
use App\Http\Controllers\Auth\PanelSessionController;
use App\Http\Controllers\Auth\SesionAnonimaController;
use App\Http\Controllers\CasoController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CicloController;
use App\Http\Controllers\CitaLaboratorioController;
use App\Http\Controllers\ClinicaController;
use App\Http\Controllers\ComprobanteController;
use App\Http\Controllers\DiagnosticoController;
use App\Http\Controllers\DispositivoController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\DoctorPacienteController;
use App\Http\Controllers\ElegibilidadController;
use App\Http\Controllers\EstudioMedicoController;
use App\Http\Controllers\LaboratorioController;
use App\Http\Controllers\LicenciaController;
use App\Http\Controllers\LicenciaUsuarioController;
use App\Http\Controllers\MedicamentoController;
use App\Http\Controllers\MedicionController;
use App\Http\Controllers\MensajeController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\PacienteMedicamentoController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\PrecalificacionController;
use App\Http\Controllers\PrecalificacionRespuestaController;
use App\Http\Controllers\PreguntaPrecalificacionController;
use App\Http\Controllers\ReglaAlertaController;
use App\Http\Controllers\SolicitudAccesoDoctorController;
use App\Http\Controllers\SuscripcionController;
use App\Http\Controllers\TipoEstudioController;
use App\Http\Controllers\TomaController;
use App\Http\Controllers\TratamientoController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Registra las cinco operaciones CRUD de una entidad.
 */
$crud = function (string $prefijo, string $controlador): void {
    Route::get($prefijo, [$controlador, 'listar']);
    Route::post($prefijo, [$controlador, 'crear']);
    Route::get($prefijo.'/{id}', [$controlador, 'ver'])->whereNumber('id');
    Route::put($prefijo.'/{id}', [$controlador, 'actualizar'])->whereNumber('id');
    Route::patch($prefijo.'/{id}', [$controlador, 'actualizar'])->whereNumber('id');
    Route::delete($prefijo.'/{id}', [$controlador, 'eliminar'])->whereNumber('id');
};

// =============================================================== rutas publicas

Route::post('/auth/auth0', [Auth0SessionController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('auth0.login');

// Login local del panel de gestion (admin/doctor) con correo y contrasena.
Route::post('/auth/panel', [PanelSessionController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('panel.login');

// El filtro clinico corre antes de crear la cuenta: no puede exigir sesion.
Route::get('/precalificacion/preguntas', [PreguntaPrecalificacionController::class, 'publicas'])
    ->middleware('throttle:30,1');

Route::post('/precalificacion/evaluar', [PrecalificacionController::class, 'evaluar'])
    ->middleware('throttle:10,1');

// El paciente empieza sin cuenta: identidad temporal + token. La reclama
// despues en POST /auth/auth0 mandando este mismo Bearer.
Route::post('/auth/anonimo', [SesionAnonimaController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('auth.anonimo');

// El doctor pide acceso desde la landing, sin cuenta todavia: la solicitud
// queda pendiente hasta que un admin verifique la matricula.
Route::post('/acceso-doctor/solicitar', [SolicitudAccesoDoctorController::class, 'solicitar'])
    ->middleware('throttle:5,1')
    ->name('acceso-doctor.solicitar');

// Destino de los enlaces temporales de POST /archivos/{id}/enlace. La firma es la
// credencial: sirve para el <Image src> de la app, donde no se puede mandar el
// Bearer. Emitirlo si exige sesion y alcance.
Route::get('/archivos/firmado/{id}', [ArchivoController::class, 'servirFirmado'])
    ->middleware(['signed', 'throttle:60,1'])
    ->whereNumber('id')
    ->name('archivos.firmado');

// ========================================================== rutas autenticadas

Route::middleware('auth:sanctum')->group(function () use ($crud) {
    Route::post('/auth/logout', [Auth0SessionController::class, 'destroy'])->name('logout');

    Route::get('/user', fn (Request $request) => $request->user()->load(['doctor.clinica', 'paciente']));

    // Autoedicion del perfil propio: los CRUD de abajo reservan usuarios y
    // pacientes a admin/doctor, este es el unico camino de escritura del
    // paciente sobre sus datos personales.
    Route::patch('/perfil', [PerfilController::class, 'actualizar'])->name('perfil.actualizar');

    // ------------------------------------------- dominio 1: identidad

    $crud('usuarios', UsuarioController::class);
    $crud('clinicas', ClinicaController::class);
    $crud('doctores', DoctorController::class);
    $crud('solicitudes-acceso-doctor', SolicitudAccesoDoctorController::class);
    $crud('pacientes', PacienteController::class);
    $crud('antecedentes-familiares', AntecedenteFamiliarController::class);
    $crud('doctor-paciente', DoctorPacienteController::class);
    $crud('dispositivos', DispositivoController::class);

    Route::post('archivos/subir', [ArchivoController::class, 'subir']);
    Route::get('archivos/{id}/descargar', [ArchivoController::class, 'descargar'])->whereNumber('id');
    Route::post('archivos/{id}/enlace', [ArchivoController::class, 'enlace'])->whereNumber('id');
    $crud('archivos', ArchivoController::class);

    Route::get('audit-logs', [AuditLogController::class, 'listar']);
    Route::get('audit-logs/{id}', [AuditLogController::class, 'ver'])->whereNumber('id');

    // ------------------------------- dominio 2: filtro, estudios, elegibilidad

    $crud('preguntas-precalificacion', PreguntaPrecalificacionController::class);
    Route::post('precalificaciones/{id}/vincular', [PrecalificacionController::class, 'vincular'])
        ->whereNumber('id');
    $crud('precalificaciones', PrecalificacionController::class);
    $crud('precalificacion-respuestas', PrecalificacionRespuestaController::class);
    $crud('tipo-estudios', TipoEstudioController::class);

    Route::post('estudios-medicos/{id}/validar', [EstudioMedicoController::class, 'validarEstudio'])->whereNumber('id');
    $crud('estudios-medicos', EstudioMedicoController::class);

    $crud('laboratorios', LaboratorioController::class);
    $crud('citas-laboratorio', CitaLaboratorioController::class);
    $crud('elegibilidades', ElegibilidadController::class);

    // ----------------------------------------- dominio 3: atencion clinica

    $crud('ciclos', CicloController::class);

    Route::post('casos/{id}/asignar', [CasoController::class, 'asignar'])->whereNumber('id');
    Route::post('casos/{id}/cerrar', [CasoController::class, 'cerrar'])->whereNumber('id');
    $crud('casos', CasoController::class);

    $crud('mediciones', MedicionController::class);
    $crud('reglas-alerta', ReglaAlertaController::class);

    Route::post('alertas/{id}/atender', [AlertaController::class, 'atender'])->whereNumber('id');
    $crud('alertas', AlertaController::class);

    Route::post('diagnosticos/{id}/firmar', [DiagnosticoController::class, 'firmar'])->whereNumber('id');
    $crud('diagnosticos', DiagnosticoController::class);

    Route::post('tratamientos/{id}/firmar', [TratamientoController::class, 'firmar'])->whereNumber('id');
    Route::post('tratamientos/{id}/enviar', [TratamientoController::class, 'enviar'])->whereNumber('id');
    Route::post('tratamientos/{id}/reemplazar', [TratamientoController::class, 'reemplazar'])->whereNumber('id');
    $crud('tratamientos', TratamientoController::class);

    $crud('medicamentos', MedicamentoController::class);
    $crud('paciente-medicamentos', PacienteMedicamentoController::class);

    Route::post('tomas/{id}/marcar', [TomaController::class, 'marcar'])->whereNumber('id');
    $crud('tomas', TomaController::class);

    // Historial del paciente (tomas marcadas + mediciones) para la app.
    Route::get('actividad', [ActividadController::class, 'listar']);

    // ------------------------------------------------ dominio 4: comercial

    $crud('planes', PlanController::class);
    $crud('suscripciones', SuscripcionController::class);

    Route::post('pagos/{id}/confirmar', [PagoController::class, 'confirmar'])->whereNumber('id');
    $crud('pagos', PagoController::class);

    $crud('comprobantes', ComprobanteController::class);

    Route::post('licencias/{id}/suspender', [LicenciaController::class, 'suspender'])->whereNumber('id');
    $crud('licencias', LicenciaController::class);

    $crud('licencia-usuarios', LicenciaUsuarioController::class);
    $crud('chats', ChatController::class);

    Route::post('mensajes/{id}/leido', [MensajeController::class, 'marcarLeido'])->whereNumber('id');
    $crud('mensajes', MensajeController::class);

    Route::post('notificaciones/{id}/leida', [NotificacionController::class, 'marcarLeida'])->whereNumber('id');
    $crud('notificaciones', NotificacionController::class);

    $crud('articulos-ayuda', ArticuloAyudaController::class);
});
