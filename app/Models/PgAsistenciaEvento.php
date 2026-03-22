<?php

namespace App\Models;

use App\Models\Concerns\EstadoSoftDeletes;
use App\Models\Concerns\GeneraIdVarchar;
use Illuminate\Database\Eloquent\Model;

class PgAsistenciaEvento extends Model
{
    use EstadoSoftDeletes, GeneraIdVarchar;

    protected $table = 'pg_asistencia_evento';

    public $incrementing = false;
    protected $keyType = 'string';

    public const OBJETO_CONTROL = 'PG_ASISTENCIA_EVENTO';

    protected $fillable = [
        'id',
        'evento_id',
        'persona_id',
        'fecha',
        'id_archivo',
        'asistencia_lote_id',
        'estado_asistencia',
        'observacion',
        // auditoría
        'creado_por',
        'actualizado_por',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'date',
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

    public function lote()
    {
        return $this->belongsTo(PgAsistenciaLote::class, 'asistencia_lote_id', 'id');
    }
}
