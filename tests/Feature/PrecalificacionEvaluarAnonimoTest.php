<?php

namespace Tests\Feature;

use App\Models\Paciente;
use App\Models\Precalificacion;
use App\Models\User;
use Database\Seeders\PreguntaPrecalificacionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrecalificacionEvaluarAnonimoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PreguntaPrecalificacionSeeder::class);
    }

    public function test_con_bearer_anonimo_la_precalificacion_queda_a_su_nombre(): void
    {
        $respuestas = $this->respuestasAptas();

        $alta = $this->postJson('/api/auth/anonimo')->assertCreated();
        $propio = Paciente::where('usuarioId', $alta->json('usuario.id'))->sole();

        $otroUsuario = User::factory()->create(['rol' => User::ROL_PACIENTE]);
        $otro = Paciente::create(['usuarioId' => $otroUsuario->id]);

        // El pacienteId del cuerpo se ignora: manda la sesion.
        $this->withToken($alta->json('token'))->postJson('/api/precalificacion/evaluar', [
            'pacienteId' => $otro->id,
            'respuestas' => $respuestas,
        ])->assertCreated()->assertJsonPath('pacienteId', $propio->id);

        $this->assertSame($propio->id, Precalificacion::sole()->pacienteId);
    }

    public function test_sin_bearer_sigue_siendo_anonima_con_lead_email(): void
    {
        $respuestas = $this->respuestasAptas();

        $this->postJson('/api/precalificacion/evaluar', [
            'leadEmail' => 'lead@glucy.test',
            'respuestas' => $respuestas,
        ])->assertCreated()
            ->assertJsonPath('pacienteId', null)
            ->assertJsonPath('leadEmail', 'lead@glucy.test');
    }

    public function test_un_bearer_invalido_responde_401(): void
    {
        $respuestas = $this->respuestasAptas();

        $this->withToken('1|token-inexistente')->postJson('/api/precalificacion/evaluar', [
            'respuestas' => $respuestas,
        ])->assertUnauthorized();

        $this->assertSame(0, Precalificacion::count());
    }

    /**
     * Todas las preguntas activas respondidas sin disparar ninguna alarma.
     * Se calcula ANTES de fijar el Bearer del test para no arrastrarlo.
     *
     * @return array<int, array{preguntaId: int, respuesta: string}>
     */
    private function respuestasAptas(): array
    {
        return collect($this->getJson('/api/precalificacion/preguntas')->assertOk()->json())
            ->map(fn (array $p) => [
                'preguntaId' => $p['id'],
                // q1 ("¿Tienes 18 años o más?") alarma con 'no'; el resto con 'si'.
                'respuesta' => $p['codigo'] === 'q1' ? 'si' : 'no',
            ])
            ->all();
    }
}
