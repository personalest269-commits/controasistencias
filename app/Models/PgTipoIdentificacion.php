<?php

namespace App\Models;

use App\Models\Concerns\EstadoSoftDeletes;
use App\Models\Concerns\GeneraIdVarchar;
use Illuminate\Database\Eloquent\Model;

class PgTipoIdentificacion extends Model
{
    use EstadoSoftDeletes, GeneraIdVarchar;

    public const OBJETO_CONTROL = 'PG_TIPO_IDENTIFICACION';

    protected $table = 'pg_tipo_identificacion';

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'codigo',
        'descripcion',
        'estado_actual',
        'asocia_persona',
        'validar',
        'longitud',
        'longitud_fija',
        'codigo_sri',
        'estado',
    ];

    protected $casts = [
        'estado_actual' => 'integer',
        'asocia_persona' => 'integer',
        'validar' => 'integer',
        'longitud' => 'integer',
        'longitud_fija' => 'integer',
    ];
}
