<?php

namespace App\Support;

use App\Models\Doctor;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Resuelve que filas puede ver cada rol.
 *
 * - admin: todo.
 * - doctor: los pacientes de su clinica mas los que tenga asignados. La clinica
 *   es la frontera de tenant; la asignacion sola impediria tomar un ingreso nuevo.
 * - paciente: solo lo propio.
 */
class Alcance
{
    public static function pacienteId(User $usuario): ?int
    {
        return Paciente::where('usuarioId', $usuario->id)->value('id');
    }

    public static function doctor(User $usuario): ?Doctor
    {
        return Doctor::where('usuarioId', $usuario->id)->first();
    }

    /**
     * IDs de paciente visibles. `null` significa sin restriccion.
     *
     * @return array<int>|null
     */
    public static function pacientesVisibles(User $usuario): ?array
    {
        if ($usuario->esAdmin()) {
            return null;
        }

        if ($usuario->esPaciente()) {
            $id = self::pacienteId($usuario);

            return $id === null ? [] : [$id];
        }

        $doctor = self::doctor($usuario);

        if ($doctor === null) {
            return [];
        }

        $deLaClinica = Paciente::where('clinicaId', $doctor->clinicaId)->pluck('id');

        $asignados = DB::table('doctor_paciente')
            ->where('doctorId', $doctor->id)
            ->where('activo', true)
            ->pluck('pacienteId');

        return $deLaClinica->merge($asignados)->unique()->values()->all();
    }

    /**
     * IDs de clinica visibles. `null` significa sin restriccion.
     *
     * @return array<int>|null
     */
    public static function clinicasVisibles(User $usuario): ?array
    {
        if ($usuario->esAdmin()) {
            return null;
        }

        if ($usuario->esDoctor()) {
            $clinicaId = self::doctor($usuario)?->clinicaId;

            return $clinicaId === null ? [] : [$clinicaId];
        }

        $clinicaId = Paciente::where('usuarioId', $usuario->id)->value('clinicaId');

        return $clinicaId === null ? [] : [$clinicaId];
    }
}
