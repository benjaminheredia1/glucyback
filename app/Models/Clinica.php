<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Clinica extends Model
{
    use SoftDeletes;

    protected $table = 'clinicas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'direccion',
        'telefono',
        'estado',
        'nit',
        'email',
        'planId',
        'usuarioId',
    ];

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuarioId');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'planId');
    }

    public function doctores(): HasMany
    {
        return $this->hasMany(Doctor::class, 'clinicaId');
    }

    public function pacientes(): HasMany
    {
        return $this->hasMany(Paciente::class, 'clinicaId');
    }

    public function licencias(): HasMany
    {
        return $this->hasMany(Licencia::class, 'clinicaId');
    }

    public function reglasAlerta(): HasMany
    {
        return $this->hasMany(ReglaAlerta::class, 'clinicaId');
    }
}
