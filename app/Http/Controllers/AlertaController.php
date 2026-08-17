<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\User;
use App\Support\Alcance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertaController extends BaseCrudController
{
    protected string $modelo = Alerta::class;

    protected array $rolesEscritura = [User::ROL_ADMIN, User::ROL_DOCTOR];

    protected array $with = ['medicion', 'paciente.usuario'];

    protected ?string $columnaPaciente = 'pacienteId';

    protected array $filtrables = ['pacienteId', 'tipo', 'severidad', 'estado'];

    protected array $ordenables = ['id', 'created_at', 'severidad'];

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'pacienteId' => [$req, 'exists:pacientes,id'],
            'medicionId' => ['nullable', 'exists:mediciones,id'],
            'reglaId' => ['nullable', 'exists:reglas_alerta,id'],
            'casoId' => ['nullable', 'exists:casos,id'],
            'tipo' => [$req, 'in:valor_critico,sin_registro,estudio_vencido,ciclo_vencido'],
            'severidad' => [$req, 'in:critica,alta,media'],
            'mensaje' => [$req, 'string', 'max:255'],
            'estado' => ['sometimes', 'in:abierta,vista,atendida'],
        ];
    }

    public function atender(Request $request, int $id): JsonResponse
    {
        $this->autorizarEscritura($request);

        $alerta = $this->consulta($request)->findOrFail($id);

        abort_if($alerta->estado === 'atendida', 409, 'La alerta ya fue atendida.');

        $antes = $alerta->toArray();

        $alerta->update([
            'estado' => 'atendida',
            'atendidaPor' => Alcance::doctor($request->user())?->id,
            'atendidaEn' => now(),
        ]);

        $this->auditar($request, 'atender', $alerta, $antes, $alerta->toArray());

        return response()->json($alerta->fresh($this->with));
    }
}
