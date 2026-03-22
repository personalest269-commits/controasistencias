<?php

namespace App\Models;

use App\Models\Concerns\EstadoSoftDeletes;
use App\Models\Concerns\GeneraIdVarchar;
use Illuminate\Database\Eloquent\Model;

class PgAsistenciaLote extends Model
{
    use EstadoSoftDeletes, GeneraIdVarchar;

    protected $table = 'pg_asistencia_lote';

    public $incrementing = false;
    protected $keyType = 'string';

    public const OBJETO_CONTROL = 'PG_ASISTENCIA_LOTE';

    protected $fillable = [
        'id',
        'evento_id',
        'departamento_id',
        'fecha',
        'observacion',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function evento()
    {
        return $this->belongsTo(PgEvento::class, 'evento_id', 'id');
    }

    public function departamento()
    {
        return $this->belongsTo(PgDepartamento::class, 'departamento_id', 'id');
    }

    public function archivos()
    {
        return $this->hasMany(PgAsistenciaLoteArchivo::class, 'asistencia_lote_id', 'id')
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            });
    }
}
