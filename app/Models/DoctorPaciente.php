<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorPaciente extends Model
{
    protected $table = 'doctor_paciente';

    protected $fillable = [
        'doctorId',
        'pacienteId',
        'desde',
        'hasta',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'desde' => 'date',
            'hasta' => 'date',
            'activo' => 'boolean',
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctorId');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'pacienteId');
    }
}
