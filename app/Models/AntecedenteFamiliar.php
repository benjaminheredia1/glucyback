<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AntecedenteFamiliar extends Model
{
    protected $table = 'antecedentes_familiares';

    protected $fillable = [
        'pacienteId',
        'condicion',
        'parentesco',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'pacienteId');
    }
}
