<?php

namespace App\Http\Controllers;

use App\Models\Laboratorio;
use App\Models\User;
use Illuminate\Http\Request;
use App\Ai\Agents\AnalistaMedico;
use Laravel\Ai\Enums\Lab;

class LaboratorioController extends BaseCrudController
{
    protected string $modelo = Laboratorio::class;

    protected array $rolesEscritura = [User::ROL_ADMIN];

    protected array $filtrables = ['activo', 'aDomicilio'];

    protected array $ordenables = ['id', 'nombre'];

    public function prueba(Request $request)
    {
        $data = $request->validate([
            "mensaje" => ["required", "string"],
        ]);
        $response = (new AnalistaMedico())->prompt($data['mensaje'], provider: Lab::OpenAI, model: 'gpt-5.6-terra', timeout: 120);
        return response()->json(['message' => 'Prueba exitosa', 'data' => $response]);
        }
    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'nombre' => [$req, 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'cobertura' => ['nullable', 'string', 'max:255'],
            'aDomicilio' => ['sometimes', 'boolean'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }
}
