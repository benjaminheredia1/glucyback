<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Da de baja a los pacientes anonimos que nunca reclamaron su cuenta.
 *
 * POST /auth/anonimo es publico: sin esta purga la tabla users crece sin
 * limite. Solo se toca a quien no mostro actividad en el plazo; un anonimo
 * que sigue usando su token no se pierde. Estudios y archivos no se tocan
 * (evidencia clinica): quedan colgando de un paciente soft-deleted e
 * inaccesibles porque no queda ningun token.
 */
class PurgarUsuariosAnonimos extends Command
{
    protected $signature = 'usuarios:purgar-anonimos
                            {--dias= : Dias sin actividad; por defecto config auth.anonimos.dias_vigencia}
                            {--dry-run : Lista los candidatos sin borrar nada}';

    protected $description = 'Da de baja a los pacientes anonimos sin actividad';

    public function handle(): int
    {
        $dias = (int) ($this->option('dias') ?? config('auth.anonimos.dias_vigencia', 30));
        $limite = now()->subDays($dias);

        $candidatos = User::query()
            ->whereNull('email')
            ->whereNull('auth0Sub')
            ->where('rol', User::ROL_PACIENTE)
            ->where('created_at', '<', $limite)
            ->where('updated_at', '<', $limite)
            // last_used_at lo actualiza Sanctum en cada peticion autenticada:
            // es la senal de actividad real.
            ->whereDoesntHave('tokens', fn (Builder $q) => $q->where('last_used_at', '>=', $limite))
            ->get();

        if ($this->option('dry-run')) {
            $this->info("Candidatos a purga (sin actividad en {$dias} dias): {$candidatos->count()}");
            $candidatos->each(fn (User $usuario) => $this->line("  #{$usuario->id} creado {$usuario->created_at}"));

            return self::SUCCESS;
        }

        foreach ($candidatos as $usuario) {
            DB::transaction(function () use ($usuario) {
                $antes = $usuario->toArray();

                $usuario->tokens()->delete();
                // Soft delete: Paciente y User usan SoftDeletes.
                Paciente::where('usuarioId', $usuario->id)->delete();
                $usuario->delete();

                AuditLog::create([
                    'usuarioId' => null,
                    'entidad' => class_basename($usuario),
                    'entidadId' => $usuario->getKey(),
                    'accion' => 'purgar-anonimo',
                    'antes' => $antes,
                    'despues' => null,
                    'ip' => null,
                ]);
            });
        }

        $this->info("Anonimos purgados: {$candidatos->count()} (sin actividad en {$dias} dias).");

        return self::SUCCESS;
    }
}
