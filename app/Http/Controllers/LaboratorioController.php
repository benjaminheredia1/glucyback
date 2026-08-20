<?php

namespace App\Http\Controllers;

use App\Ai\Agents\AnalistaMedico;
use App\Models\Laboratorio;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Ai\Enums\Lab;
use OpenApi\Attributes as OA;

class LaboratorioController extends BaseCrudController
{
    protected string $modelo = Laboratorio::class;

    protected array $rolesEscritura = [User::ROL_ADMIN];

    protected array $filtrables = ['activo', 'aDomicilio'];

    protected array $ordenables = ['id', 'nombre'];

    #[OA\Post(
        path: '/prueba',
        tags: ['Precalificacion'],
        summary: 'Prueba del agente AnalistaMedico (temporal)',
        description: 'Endpoint de desarrollo: manda un mensaje al agente AI con el contexto del paciente indicado y devuelve la respuesta cruda.',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['mensaje', 'pacienteId'],
            properties: [
                new OA\Property(property: 'mensaje', type: 'string'),
                new OA\Property(property: 'pacienteId', type: 'integer'),
            ],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Respuesta del agente', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'data', type: 'object'),
            ])),
            new OA\Response(response: 404, ref: '#/components/responses/NoEncontrado'),
            new OA\Response(response: 422, ref: '#/components/responses/Validacion'),
        ],
    )]
    public function prueba(Request $request)
    {
        $data = $request->validate([
            'mensaje' => ['required', 'string'],
            'pacienteId' => ['required', 'integer', 'exists:pacientes,id'],
        ]);

        $agente = new AnalistaMedico(Paciente::findOrFail($data['pacienteId']));

        $response = $agente->prompt($data['mensaje'], provider: Lab::OpenAI, model: 'gpt-5.6-terra', timeout: 120);

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
