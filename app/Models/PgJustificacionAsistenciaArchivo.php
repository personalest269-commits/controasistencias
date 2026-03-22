<?php

namespace App\Models;

use App\Models\Concerns\EstadoSoftDeletes;
use App\Models\Concerns\GeneraIdVarchar;
use Illuminate\Database\Eloquent\Model;

class PgJustificacionAsistenciaArchivo extends Model
{
    use EstadoSoftDeletes, GeneraIdVarchar;

    protected $table = 'pg_justificacion_asistencia_archivo';

    public $incrementing = false;
    protected $keyType = 'string';

    public const OBJETO_CONTROL = 'PG_JUSTIFICACION_ASISTENCIA_ARCHIVO';

    protected $fillable = [
        'id',
        'justificacion_id',
        'id_archivo',
        'estado',
    ];

    public function justificacion()
    {
        return $this->belongsTo(PgJustificacionAsistencia::class, 'justificacion_id', 'id');
    }

    public function archivo()
    {
        return $this->belongsTo(AdArchivoDigital::class, 'id_archivo', 'id');
    }
}
