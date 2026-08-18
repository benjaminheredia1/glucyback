<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudAccesoDoctor extends Model
{
    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_APROBADA = 'aprobada';

    public const ESTADO_RECHAZADA = 'rechazada';

    protected $table = 'solicitudes_acceso_doctor';

    protected $fillable = [
        'nombre',
        'matricula',
        'especialidad',
        'correo',
        'institucion',
        'estado',
        'ip',
    ];

    /**
     * La IP se guarda para rastrear abuso de una ruta publica, no es dato del
     * solicitante: no viaja en las respuestas de la API.
     *
     * @var array<int, string>
     */
    protected $hidden = ['ip'];
}
