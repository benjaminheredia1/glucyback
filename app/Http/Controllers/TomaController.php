<?php

namespace App\Http\Controllers;

use App\Models\Toma;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TomaController extends BaseCrudController
{
    protected string $modelo = Toma::class;

    protected array $rolesEscritura = [User::ROL_ADMIN, User::ROL_DOCTOR, User::ROL_PACIENTE];

    protected array $with = ['pacienteMedicamento.medicamento'];

    protected ?array $pacienteViaRelacion = ['relacion' => 'pacienteMedicamento', 'columna' => 'pacienteId'];

    protected array $filtrables = ['pacienteMedicamentoId', 'estado'];

    protected array $ordenables = ['id', 'programadaEn'];

    protected string $ordenPorDefecto = 'programadaEn';

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'pacienteMedicamentoId' => [$req, 'exists:paciente_medicamentos,id'],
            'programadaEn' => [$req, 'date'],
            'tomadaEn' => ['nullable', 'date'],
            'estado' => ['sometimes', 'in:pendiente,tomada,omitida'],
        ];
    }

    public function marcar(Request $request, int $id): JsonResponse
    {
        $this->autorizarEscritura($request);

        $toma = $this->consulta($request)->findOrFail($id);

        $datos = $request->validate([
            'estado' => ['required', 'in:tomada,omitida'],
        ]);

        $antes = $toma->toArray();

        $toma->update([
            'estado' => $datos['estado'],
            'tomadaEn' => $datos['estado'] === 'tomada' ? now() : null,
        ]);

        $this->auditar($request, 'marcar', $toma, $antes, $toma->toArray());

        return response()->json($toma->fresh($this->with));
    }
}
