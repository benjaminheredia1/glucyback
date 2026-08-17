<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\Comprobante;
use App\Models\Medicamento;
use App\Models\Mensaje;
use App\Models\Paciente;
use App\Models\PacienteMedicamento;
use App\Models\Pago;
use App\Models\Plan;
use App\Models\Suscripcion;
use App\Models\Toma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Entidades que no tienen `pacienteId` propio y se filtran por relacion:
 * tomas -> pacienteMedicamento, mensajes -> chat, comprobantes -> pago.suscripcion.
 */
class AlcanceIndirectoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Paciente}
     */
    private function crearPaciente(string $email): array
    {
        $usuario = User::create([
            'name' => 'Paciente',
            'email' => $email,
            'password' => 'secret1234',
            'rol' => User::ROL_PACIENTE,
        ]);

        $paciente = Paciente::create([
            'usuarioId' => $usuario->id,
            'fechaNacimiento' => '1970-01-01',
            'tipoDiabetes' => 'DM2',
        ]);

        return [$usuario, $paciente];
    }

    private function crearToma(Paciente $paciente): Toma
    {
        $medicamento = Medicamento::create(['nombre' => 'Metformina '.uniqid()]);

        $prescripcion = PacienteMedicamento::create([
            'pacienteId' => $paciente->id,
            'medicamentoId' => $medicamento->id,
            'dosis' => '850 mg',
            'frecuencia' => 'cada 12 h',
            'fechaInicio' => now()->toDateString(),
        ]);

        return Toma::create([
            'pacienteMedicamentoId' => $prescripcion->id,
            'programadaEn' => now(),
        ]);
    }

    private function crearComprobante(Paciente $paciente): Comprobante
    {
        $plan = Plan::create([
            'nombre' => 'Mensual '.uniqid(),
            'ambito' => 'paciente',
            'precio' => 25,
            'periodicidad' => 'mensual',
        ]);

        $suscripcion = Suscripcion::create([
            'pacienteId' => $paciente->id,
            'planId' => $plan->id,
            'estado' => 'activa',
            'inicio' => now()->toDateString(),
        ]);

        $pago = Pago::create([
            'suscripcionId' => $suscripcion->id,
            'monto' => 25,
            'metodo' => 'qr',
            'estado' => 'pagado',
        ]);

        return Comprobante::create([
            'pagoId' => $pago->id,
            'numero' => 'F-'.uniqid(),
            'emitidoEn' => now(),
        ]);
    }

    public function test_las_tomas_se_filtran_por_la_prescripcion_del_paciente(): void
    {
        [$usuarioA, $pacienteA] = $this->crearPaciente('a@glucy.test');
        [, $pacienteB] = $this->crearPaciente('b@glucy.test');

        $propia = $this->crearToma($pacienteA);
        $ajena = $this->crearToma($pacienteB);

        $ids = collect($this->actingAs($usuarioA)->getJson('/api/tomas')->assertOk()->json('data'))
            ->pluck('id')
            ->all();

        $this->assertSame([$propia->id], $ids);

        $this->actingAs($usuarioA)
            ->postJson("/api/tomas/{$ajena->id}/marcar", ['estado' => 'tomada'])
            ->assertNotFound();
    }

    public function test_los_mensajes_se_filtran_por_el_chat_del_paciente(): void
    {
        [$usuarioA, $pacienteA] = $this->crearPaciente('a@glucy.test');
        [, $pacienteB] = $this->crearPaciente('b@glucy.test');

        $chatA = Chat::create(['nombre' => 'Soporte A', 'pacienteId' => $pacienteA->id]);
        $chatB = Chat::create(['nombre' => 'Soporte B', 'pacienteId' => $pacienteB->id]);

        $propio = Mensaje::create(['chatId' => $chatA->id, 'texto' => 'hola', 'pacienteId' => $pacienteA->id]);
        Mensaje::create(['chatId' => $chatB->id, 'texto' => 'ajeno', 'pacienteId' => $pacienteB->id]);

        $ids = collect($this->actingAs($usuarioA)->getJson('/api/mensajes')->assertOk()->json('data'))
            ->pluck('id')
            ->all();

        $this->assertSame([$propio->id], $ids);

        $this->actingAs($usuarioA)
            ->postJson('/api/mensajes', ['chatId' => $chatB->id, 'texto' => 'intruso'])
            ->assertForbidden();

        $this->assertDatabaseMissing('mensajes', ['texto' => 'intruso']);
    }

    public function test_los_comprobantes_se_filtran_por_pago_y_suscripcion(): void
    {
        [$usuarioA, $pacienteA] = $this->crearPaciente('a@glucy.test');
        [, $pacienteB] = $this->crearPaciente('b@glucy.test');

        $propio = $this->crearComprobante($pacienteA);
        $ajeno = $this->crearComprobante($pacienteB);

        $ids = collect($this->actingAs($usuarioA)->getJson('/api/comprobantes')->assertOk()->json('data'))
            ->pluck('id')
            ->all();

        $this->assertSame([$propio->id], $ids);

        $this->actingAs($usuarioA)
            ->getJson("/api/comprobantes/{$ajeno->id}")
            ->assertNotFound();
    }
}
