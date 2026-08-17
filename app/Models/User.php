<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    public const ROL_ADMIN = 'admin';

    public const ROL_DOCTOR = 'doctor';

    public const ROL_PACIENTE = 'paciente';

    /** Nombre con el que nace un paciente anonimo hasta que lo complete o lo reclame. */
    public const NOMBRE_TEMPORAL = 'Paciente';

    protected $fillable = [
        'name',
        'apellidoPaterno',
        'apellidoMaterno',
        'email',
        'auth0Sub',
        'email_verified_at',
        'telefono',
        'password',
        'rol',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @var array<int, string> */
    protected $appends = ['esTemporal'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function doctor(): HasOne
    {
        return $this->hasOne(Doctor::class, 'usuarioId');
    }

    public function paciente(): HasOne
    {
        return $this->hasOne(Paciente::class, 'usuarioId');
    }

    public function clinicas(): HasMany
    {
        return $this->hasMany(Clinica::class, 'usuarioId');
    }

    public function archivos(): HasMany
    {
        return $this->hasMany(Archivo::class, 'usuarioId');
    }

    public function dispositivos(): HasMany
    {
        return $this->hasMany(Dispositivo::class, 'usuarioId');
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'usuarioId');
    }

    public function licenciaUsuarios(): HasMany
    {
        return $this->hasMany(LicenciaUsuario::class, 'usuarioId');
    }

    public function esAdmin(): bool
    {
        return $this->rol === self::ROL_ADMIN;
    }

    public function esDoctor(): bool
    {
        return $this->rol === self::ROL_DOCTOR;
    }

    public function esPaciente(): bool
    {
        return $this->rol === self::ROL_PACIENTE;
    }

    /**
     * Paciente anonimo: entro por POST /auth/anonimo y todavia no reclamo la
     * cuenta con Auth0. Se deriva del correo para que no exista un estado que
     * pueda quedar desincronizado: al reclamar se escribe el email y deja de
     * ser temporal solo.
     */
    public function esTemporal(): bool
    {
        return $this->email === null;
    }

    public function getEsTemporalAttribute(): bool
    {
        return $this->esTemporal();
    }
}
