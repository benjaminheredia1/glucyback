<?php

namespace Tests\Feature;

use App\Models\Clinica;
use App\Models\Doctor;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiCerradaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Paciente}
     */
    private function crearPaciente(string $email, ?int $clinicaId = null): array
    {
        $usuario = User::create([
            'name' => 'Paciente',
            'email' => $email,
            'password' => 'secret1234',
            'rol' => User::ROL_PACIENTE,
        ]);

        $paciente = Paciente::create([
            'usuarioId' => $usuario->id,
            'clinicaId' => $clinicaId,
            'fechaNacimiento' => '1970-01-01',
            'tipoDiabetes' => 'DM2',
        ]);

        return [$usuario, $paciente];
    }

    private function crearClinica(): Clinica
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin'.uniqid().'@glucy.test',
            'password' => 'secret1234',
            'rol' => User::ROL_ADMIN,
        ]);

        return Clinica::create([
            'nombre' => 'Clinica '.uniqid(),
            'direccion' => 'Calle 1',
            'telefono' => '123',
            'usuarioId' => $admin->id,
        ]);
    }

    public function test_las_rutas_crud_rechazan_peticiones_anonimas(): void
    {
        foreach (['pacientes', 'usuarios', 'estudios-medicos', 'tratamientos', 'mediciones', 'archivos'] as $recurso) {
            $this->getJson("/api/{$recurso}")->assertUnauthorized();
            $this->deleteJson("/api/{$recurso}/1")->assertUnauthorized();
        }
    }

    /**
     * `getJson()`/`postJson()` mandan "Accept: application/json" solos, igual
     * que dio_client.dart en la app. Un cliente que no lo mande -curl a secas,
     * un navegador, Postman sin configurar- tiene que recibir el mismo 401 y
     * no un 500: la app es solo API, no existe pantalla de login que
     * redirigir. Con `get()` plano (sin ese header) se prueba justo esa via,
     * la unica en la que el bug se manifestaba.
     */
    public function test_una_peticion_sin_accept_json_tambien_recibe_401_no_500(): void
    {
        $this->get('/api/user')
            ->assertUnauthorized()
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_un_paciente_no_ve_los_datos_de_otro(): void
    {
        [$usuarioA, $pacienteA] = $this->crearPaciente('a@glucy.test');
        [, $pacienteB] = $this->crearPaciente('b@glucy.test');

        $respuesta = $this->actingAs($usuarioA)->getJson('/api/pacientes');

        $respuesta->assertOk();
        $ids = collect($respuesta->json('data'))->pluck('id')->all();

        $this->assertSame([$pacienteA->id], $ids);

        $this->actingAs($usuarioA)
            ->getJson("/api/pacientes/{$pacienteB->id}")
            ->assertNotFound();
    }

    public function test_un_doctor_no_ve_pacientes_de_otra_clinica(): void
    {
        $clinicaA = $this->crearClinica();
        $clinicaB = $this->crearClinica();

        $usuarioDoctor = User::create([
            'name' => 'Doc',
            'email' => 'doc@glucy.test',
            'password' => 'secret1234',
            'rol' => User::ROL_DOCTOR,
        ]);

        Doctor::create([
            'usuarioId' => $usuarioDoctor->id,
            'clinicaId' => $clinicaA->id,
            'matricula' => 'MP-1',
        ]);

        [, $propio] = $this->crearPaciente('propio@glucy.test', $clinicaA->id);
        [, $ajeno] = $this->crearPaciente('ajeno@glucy.test', $clinicaB->id);

        $ids = collect($this->actingAs($usuarioDoctor)->getJson('/api/pacientes')->json('data'))
            ->pluck('id')
            ->all();

        $this->assertSame([$propio->id], $ids);

        $this->actingAs($usuarioDoctor)
            ->getJson("/api/pacientes/{$ajeno->id}")
            ->assertNotFound();
    }

    public function test_un_paciente_no_puede_crear_una_medicion_a_nombre_de_otro(): void
    {
        [$usuarioA, $pacienteA] = $this->crearPaciente('a@glucy.test');
        [, $pacienteB] = $this->crearPaciente('b@glucy.test');

        $respuesta = $this->actingAs($usuarioA)->postJson('/api/mediciones', [
            'pacienteId' => $pacienteB->id,
            'valor' => 110,
            'momento' => 'ayunas',
        ]);

        $respuesta->assertCreated();
        // El pacienteId enviado se ignora y se reemplaza por el propio.
        $this->assertSame($pacienteA->id, $respuesta->json('pacienteId'));
    }

    public function test_un_doctor_no_puede_escribir_sobre_un_paciente_de_otra_clinica(): void
    {
        $clinicaA = $this->crearClinica();
        $clinicaB = $this->crearClinica();

        $usuarioDoctor = User::create([
            'name' => 'Doc',
            'email' => 'doc@glucy.test',
            'password' => 'secret1234',
            'rol' => User::ROL_DOCTOR,
        ]);

        Doctor::create([
            'usuarioId' => $usuarioDoctor->id,
            'clinicaId' => $clinicaA->id,
            'matricula' => 'MP-1',
        ]);

        [, $ajeno] = $this->crearPaciente('ajeno@glucy.test', $clinicaB->id);

        $this->actingAs($usuarioDoctor)->postJson('/api/ciclos', [
            'pacienteId' => $ajeno->id,
            'inicio' => now()->toDateString(),
            'fin' => now()->addDays(15)->toDateString(),
        ])->assertForbidden();

        $this->assertDatabaseMissing('ciclos', ['pacienteId' => $ajeno->id]);
    }

    public function test_un_paciente_no_puede_escribir_en_catalogos(): void
    {
        [$usuario] = $this->crearPaciente('a@glucy.test');

        $this->actingAs($usuario)
            ->postJson('/api/medicamentos', ['nombre' => 'Falso'])
            ->assertForbidden();

        $this->actingAs($usuario)
            ->getJson('/api/usuarios')
            ->assertForbidden();
    }
}
