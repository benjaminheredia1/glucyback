<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Doctor extends Model
{
    use SoftDeletes;

    protected $table = 'doctores';

    protected $fillable = [
        'usuarioId',
        'clinicaId',
        'matricula',
        'especialidad',
        'firmaArchivoId',
        'estado',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuarioId');
    }

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class, 'clinicaId');
    }

    public function firmaArchivo(): BelongsTo
    {
        return $this->belongsTo(Archivo::class, 'firmaArchivoId');
    }

    public function pacientes(): BelongsToMany
    {
        return $this->belongsToMany(Paciente::class, 'doctor_paciente', 'doctorId', 'pacienteId')
            ->withPivot(['desde', 'hasta', 'activo'])
            ->withTimestamps();
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(DoctorPaciente::class, 'doctorId');
    }

    public function casos(): HasMany
    {
        return $this->hasMany(Caso::class, 'doctorId');
    }

    public function tratamientos(): HasMany
    {
        return $this->hasMany(Tratamiento::class, 'doctorId');
    }

    public function diagnosticos(): HasMany
    {
        return $this->hasMany(Diagnostico::class, 'doctorId');
    }
}
