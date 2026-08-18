<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\SesionOpcional;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SesionOpcionalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ruta publica de prueba, sin auth:sanctum, igual que /auth/auth0. Bajo
        // /api porque bootstrap/app.php solo renderiza JSON para api/*.
        Route::get('/api/_prueba/sesion-opcional', fn (Request $request) => response()->json([
            'id' => SesionOpcional::usuario($request)?->id,
        ]));
    }

    public function test_sin_bearer_no_hay_sesion(): void
    {
        $this->getJson('/api/_prueba/sesion-opcional')
            ->assertOk()
            ->assertJsonPath('id', null);
    }

    public function test_con_bearer_valido_devuelve_al_usuario(): void
    {
        $usuario = User::factory()->create();
        $token = $usuario->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/_prueba/sesion-opcional')
            ->assertOk()
            ->assertJsonPath('id', $usuario->id);
    }

    // Un Bearer presente pero invalido no se ignora en silencio: si la app cree
    // que reclama la cuenta y no la reclama, el paciente pierde sus estudios.
    public function test_con_bearer_invalido_responde_401(): void
    {
        $this->withToken('1|token-que-no-existe')
            ->getJson('/api/_prueba/sesion-opcional')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'El token de la sesion no es valido.');
    }

    public function test_un_bearer_revocado_responde_401(): void
    {
        $usuario = User::factory()->create();
        $token = $usuario->createToken('api')->plainTextToken;
        $usuario->tokens()->delete();

        $this->withToken($token)
            ->getJson('/api/_prueba/sesion-opcional')
            ->assertUnauthorized();
    }
}
