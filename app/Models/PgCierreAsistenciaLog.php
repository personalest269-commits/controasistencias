<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PgCierreAsistenciaLog extends Model
{
    protected $table = 'pg_cierre_asistencia_log';

    protected $fillable = [
        'fecha',
        'started_at',
        'finished_at',
        'status',
        'message',
        'total_personas',
        'total_eventos',
        'faltas_nuevas',
        'faltas_actualizadas',
        'run_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'total_personas' => 'integer',
        'total_eventos' => 'integer',
        'faltas_nuevas' => 'integer',
        'faltas_actualizadas' => 'integer',
    ];
}
