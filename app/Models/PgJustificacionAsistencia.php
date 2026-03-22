<?php

namespace App\Models;

use App\Models\Concerns\EstadoSoftDeletes;
use App\Models\Concerns\GeneraIdVarchar;
use Illuminate\Database\Eloquent\Model;

class PgJustificacionAsistencia extends Model
{
    use EstadoSoftDeletes, GeneraIdVarchar;

    protected $table = 'pg_justificacion_asistencia';

    public $incrementing = false;
    protected $keyType = 'string';

    public const OBJETO_CONTROL = 'PG_JUSTIFICACION_ASISTENCIA';

    protected $fillable = [
        'id',
        'evento_id',
        'persona_id',
        'fecha',
        'motivo',
        'estado_revision',
        'revisado_por',
        'revisado_en',
        'id_archivo',
        // auditoría
        'creado_por',
        'actualizado_por',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'date',
        'revisado_en' => 'datetime',
    ];

    public function evento()
    {
        return $this->belongsTo(PgEvento::class, 'evento_id', 'id');
    }

    public function persona()
    {
        return $this->belongsTo(PgPersona::class, 'persona_id', 'id');
    }

    public function archivo()
    {
        return $this->belongsTo(AdArchivoDigital::class, 'id_archivo', 'id');
    }

    public function archivos()
    {
        return $this->hasMany(PgJustificacionAsistenciaArchivo::class, 'justificacion_id', 'id')
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            });
    }
}
