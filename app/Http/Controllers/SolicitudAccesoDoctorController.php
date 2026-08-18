<?php

namespace App\Http\Controllers;

use App\Models\SolicitudAccesoDoctor;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SolicitudAccesoDoctorController extends BaseCrudController
{
    protected string $modelo = SolicitudAccesoDoctor::class;

    /** La bandeja de solicitudes es material de verificacion: solo admin. */
    protected array $rolesLectura = [User::ROL_ADMIN];

    protected array $rolesEscritura = [User::ROL_ADMIN];

    protected array $filtrables = ['estado', 'especialidad', 'correo', 'matricula'];

    protected array $ordenables = ['id', 'nombre', 'estado', 'created_at'];

    protected string $ordenPorDefecto = 'created_at';

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';

        return [
            'nombre' => [$req, 'string', 'max:255'],
            'matricula' => [$req, 'string', 'max:100'],
            'especialidad' => [$req, 'string', 'max:100'],
            'correo' => [$req, 'email', 'max:255'],
            'institucion' => ['nullable', 'string', 'max:255'],
            'estado' => ['sometimes', 'in:pendiente,aprobada,rechazada'],
        ];
    }

    /**
     * Alta publica del formulario "Solicitar acceso" de la landing.
     *
     * No crea usuario ni doctor: el alta real exige verificar la matricula a
     * mano, asi que esto solo deja la solicitud en estado `pendiente`. El
     * estado nunca se toma del cuerpo -es una ruta anonima-, y la respuesta
     * devuelve lo minimo para que el front confirme el envio.
     */
    public function solicitar(Request $request): JsonResponse
    {
        $datos = $request->validate($this->reglas($request, true));

        $correo = Str::lower(trim($datos['correo']));
        $matricula = trim($datos['matricula']);

        // Reenviar el formulario no debe apilar filas que el admin tenga que
        // depurar. Una solicitud ya resuelta si admite intentar de nuevo.
        $pendiente = SolicitudAccesoDoctor::where('estado', SolicitudAccesoDoctor::ESTADO_PENDIENTE)
            ->where(fn ($q) => $q->where('correo', $correo)->orWhere('matricula', $matricula))
            ->first();

        if ($pendiente !== null) {
            return response()->json([
                'message' => 'Ya hay una solicitud pendiente con ese correo o matricula.',
                'id' => $pendiente->id,
                'estado' => $pendiente->estado,
            ], 409);
        }

        $solicitud = SolicitudAccesoDoctor::create([
            'nombre' => trim($datos['nombre']),
            'matricula' => $matricula,
            'especialidad' => $datos['especialidad'],
            'correo' => $correo,
            'institucion' => $datos['institucion'] ?? null,
            'estado' => SolicitudAccesoDoctor::ESTADO_PENDIENTE,
            'ip' => $request->ip(),
        ]);

        $this->auditar($request, 'solicitar', $solicitud, null, $solicitud->toArray());

        return response()->json([
            'message' => 'Solicitud recibida. Respondemos en menos de 48 horas habiles.',
            'id' => $solicitud->id,
            'estado' => $solicitud->estado,
        ], 201);
    }
}
