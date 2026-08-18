<?php

namespace Tests\Feature;

use App\Models\SolicitudAccesoDoctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SolicitudAccesoDoctorTest extends TestCase
{
    use RefreshDatabase;

    private const FORMULARIO = [
        'nombre' => 'Dr. Luis Fernandez',
        'matricula' => '45281',
        'especialidad' => 'Endocrinologia',
        'correo' => 'doctor@correo.com',
        'institucion' => 'Clinica San Rafael',
    ];

    public function test_el_formulario_publico_guarda_la_solicitud(): void
    {
        $this->postJson('/api/acceso-doctor/solicitar', self::FORMULARIO)
            ->assertCreated()
            ->assertJsonPath('estado', 'pendiente');

        $this->assertDatabaseHas('solicitudes_acceso_doctor', [
            'nombre' => 'Dr. Luis Fernandez',
            'matricula' => '45281',
            'especialidad' => 'Endocrinologia',
            'correo' => 'doctor@correo.com',
            'institucion' => 'Clinica San Rafael',
            'estado' => 'pendiente',
        ]);
    }

    public function test_la_institucion_es_opcional(): void
    {
        $datos = self::FORMULARIO;
        unset($datos['institucion']);

        $this->postJson('/api/acceso-doctor/solicitar', $datos)->assertCreated();

        $this->assertDatabaseHas('solicitudes_acceso_doctor', [
            'correo' => 'doctor@correo.com',
            'institucion' => null,
        ]);
    }

    public function test_exige_los_campos_obligatorios(): void
    {
        $this->postJson('/api/acceso-doctor/solicitar', ['correo' => 'no-es-correo'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nombre', 'matricula', 'especialidad', 'correo']);

        $this->assertDatabaseCount('solicitudes_acceso_doctor', 0);
    }

    // El alta real exige verificar la matricula a mano: si el cuerpo pudiera
    // fijar el estado, cualquiera se aprobaria a si mismo desde la landing.
    public function test_el_estado_no_se_toma_del_cuerpo(): void
    {
        $this->postJson('/api/acceso-doctor/solicitar', self::FORMULARIO + ['estado' => 'aprobada'])
            ->assertCreated()
            ->assertJsonPath('estado', 'pendiente');

        $this->assertDatabaseHas('solicitudes_acceso_doctor', [
            'correo' => 'doctor@correo.com',
            'estado' => 'pendiente',
        ]);
    }

    public function test_el_correo_se_normaliza_a_minusculas(): void
    {
        $this->postJson('/api/acceso-doctor/solicitar', array_merge(self::FORMULARIO, [
            'correo' => 'Doctor@Correo.com',
        ]))->assertCreated();

        $this->assertDatabaseHas('solicitudes_acceso_doctor', ['correo' => 'doctor@correo.com']);
    }

    public function test_rechaza_una_segunda_solicitud_pendiente_del_mismo_correo(): void
    {
        $this->postJson('/api/acceso-doctor/solicitar', self::FORMULARIO)->assertCreated();

        $this->postJson('/api/acceso-doctor/solicitar', array_merge(self::FORMULARIO, [
            'matricula' => '99999',
        ]))->assertStatus(409);

        $this->assertDatabaseCount('solicitudes_acceso_doctor', 1);
    }

    public function test_rechaza_una_segunda_solicitud_pendiente_de_la_misma_matricula(): void
    {
        $this->postJson('/api/acceso-doctor/solicitar', self::FORMULARIO)->assertCreated();

        $this->postJson('/api/acceso-doctor/solicitar', array_merge(self::FORMULARIO, [
            'correo' => 'otro@correo.com',
        ]))->assertStatus(409);

        $this->assertDatabaseCount('solicitudes_acceso_doctor', 1);
    }

    // Rechazada la primera, el doctor tiene que poder corregir y reintentar.
    public function test_admite_reintentar_cuando_la_anterior_ya_se_resolvio(): void
    {
        SolicitudAccesoDoctor::create(self::FORMULARIO + ['estado' => 'rechazada']);

        $this->postJson('/api/acceso-doctor/solicitar', self::FORMULARIO)->assertCreated();

        $this->assertDatabaseCount('solicitudes_acceso_doctor', 2);
    }

    public function test_la_solicitud_publica_queda_en_la_bitacora(): void
    {
        $this->postJson('/api/acceso-doctor/solicitar', self::FORMULARIO)->assertCreated();

        $this->assertDatabaseHas('audit_logs', [
            'usuarioId' => null,
            'entidad' => 'SolicitudAccesoDoctor',
            'accion' => 'solicitar',
        ]);
    }

    public function test_solo_el_admin_lee_la_bandeja(): void
    {
        SolicitudAccesoDoctor::create(self::FORMULARIO);

        $this->getJson('/api/solicitudes-acceso-doctor')->assertUnauthorized();

        $paciente = User::factory()->create(['rol' => User::ROL_PACIENTE]);
        $this->actingAs($paciente)->getJson('/api/solicitudes-acceso-doctor')->assertForbidden();

        $doctor = User::factory()->create(['rol' => User::ROL_DOCTOR]);
        $this->actingAs($doctor)->getJson('/api/solicitudes-acceso-doctor')->assertForbidden();

        $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
        $this->actingAs($admin)
            ->getJson('/api/solicitudes-acceso-doctor')
            ->assertOk()
            ->assertJsonPath('data.0.correo', 'doctor@correo.com');
    }

    public function test_el_admin_resuelve_la_solicitud(): void
    {
        $solicitud = SolicitudAccesoDoctor::create(self::FORMULARIO);
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);

        $this->actingAs($admin)
            ->patchJson("/api/solicitudes-acceso-doctor/{$solicitud->id}", ['estado' => 'aprobada'])
            ->assertOk()
            ->assertJsonPath('estado', 'aprobada');

        $this->assertSame('aprobada', $solicitud->fresh()->estado);
    }

    public function test_la_respuesta_no_expone_la_ip(): void
    {
        $this->postJson('/api/acceso-doctor/solicitar', self::FORMULARIO)
            ->assertCreated()
            ->assertJsonMissing(['ip' => '127.0.0.1']);

        $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);

        $this->actingAs($admin)
            ->getJson('/api/solicitudes-acceso-doctor')
            ->assertOk()
            ->assertJsonMissingPath('data.0.ip');
    }
}
