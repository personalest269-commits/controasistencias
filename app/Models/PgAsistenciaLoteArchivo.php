<?php

namespace App\Models;

use App\Models\Concerns\EstadoSoftDeletes;
use App\Models\Concerns\GeneraIdVarchar;
use Illuminate\Database\Eloquent\Model;

class PgAsistenciaLoteArchivo extends Model
{
    use EstadoSoftDeletes, GeneraIdVarchar;

    protected $table = 'pg_asistencia_lote_archivo';

    public $incrementing = false;
    protected $keyType = 'string';

    public const OBJETO_CONTROL = 'PG_ASISTENCIA_LOTE_ARCHIVO';

    protected $fillable = [
        'id',
        'asistencia_lote_id',
        'id_archivo',
        'estado',
    ];

    public function lote()
    {
        return $this->belongsTo(PgAsistenciaLote::class, 'asistencia_lote_id', 'id');
    }

    public function archivo()
    {
        return $this->belongsTo(AdArchivoDigital::class, 'id_archivo', 'id');
    }
}
