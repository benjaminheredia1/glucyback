<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Paciente extends Model
{
    use SoftDeletes;

    protected $table = 'pacientes';

    protected $fillable = [
        'usuarioId',
        'clinicaId',
        'fechaNacimiento',
        'sexo',
        'tipoDiabetes',
        'pesoKg',
        'tallaCm',
        'diagnosticadoEn',
        'alergias',
        'comorbilidades',
        'tabaquismo',
        'contactoEmergencia',
    ];

    protected function casts(): array
    {
        return [
            'fechaNacimiento' => 'date',
            'diagnosticadoEn' => 'date',
            'pesoKg' => 'decimal:2',
            'tabaquismo' => 'boolean',
        ];
    }

    /**
     * Garantiza la fila de pacientes de una cuenta con rol paciente: sin ella
     * Alcance::pacienteId() devuelve null y la API responde 422 "El usuario no
     * tiene perfil de paciente". Las altas nuevas (anonimo, Auth0, panel) la
     * crean, pero una cuenta vieja puede no tenerla; si estaba soft-deleted se
     * restaura y los datos clinicos que cuelgan de ella se conservan.
     */
    public static function asegurarPara(User $usuario): void
    {
        if (! $usuario->esPaciente()) {
            return;
        }

        $paciente = self::withTrashed()->where('usuarioId', $usuario->id)->first();

        if ($paciente === null) {
            self::create(['usuarioId' => $usuario->id]);
        } elseif ($paciente->trashed()) {
            $paciente->restore();
        }
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuarioId');
    }

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class, 'clinicaId');
    }

    public function doctores(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class, 'doctor_paciente', 'pacienteId', 'doctorId')
            ->withPivot(['desde', 'hasta', 'activo'])
            ->withTimestamps();
    }

    public function antecedentesFamiliares(): HasMany
    {
        return $this->hasMany(AntecedenteFamiliar::class, 'pacienteId');
    }

    public function precalificaciones(): HasMany
    {
        return $this->hasMany(Precalificacion::class, 'pacienteId');
    }

    public function estudiosMedicos(): HasMany
    {
        return $this->hasMany(EstudioMedico::class, 'pacienteId');
    }

    public function citasLaboratorio(): HasMany
    {
        return $this->hasMany(CitaLaboratorio::class, 'pacienteId');
    }

    public function elegibilidades(): HasMany
    {
        return $this->hasMany(Elegibilidad::class, 'pacienteId');
    }

    public function elegibilidadVigente(): HasOne
    {
        return $this->hasOne(Elegibilidad::class, 'pacienteId')->latestOfMany('generadaEn');
    }

    public function ciclos(): HasMany
    {
        return $this->hasMany(Ciclo::class, 'pacienteId');
    }

    public function cicloActivo(): HasOne
    {
        return $this->hasOne(Ciclo::class, 'pacienteId')->where('estado', 'activo');
    }

    public function casos(): HasMany
    {
        return $this->hasMany(Caso::class, 'pacienteId');
    }

    public function mediciones(): HasMany
    {
        return $this->hasMany(Medicion::class, 'pacienteId');
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(Alerta::class, 'pacienteId');
    }

    /** Medicacion actual declarada en el perfil (nombre y cantidad libres). */
    public function medicacionActual(): HasMany
    {
        return $this->hasMany(medicamntos_antiguos::class, 'paciente_id');
    }

    public function diagnosticos(): HasMany
    {
        return $this->hasMany(Diagnostico::class, 'pacienteId');
    }

    public function tratamientos(): HasMany
    {
        return $this->hasMany(Tratamiento::class, 'pacienteId');
    }

    public function medicamentos(): HasMany
    {
        return $this->hasMany(PacienteMedicamento::class, 'pacienteId');
    }

    public function suscripciones(): HasMany
    {
        return $this->hasMany(Suscripcion::class, 'pacienteId');
    }

    public function suscripcionVigente(): HasOne
    {
        return $this->hasOne(Suscripcion::class, 'pacienteId')
            ->whereIn('estado', ['prueba', 'activa']);
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class, 'pacienteId');
    }
}
