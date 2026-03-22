<?php

namespace App\Models;

use App\Models\Concerns\EstadoSoftDeletes;
use App\Models\Concerns\GeneraIdVarchar;
use Illuminate\Database\Eloquent\Model;

class PgEstadoCivil extends Model
{
    use EstadoSoftDeletes, GeneraIdVarchar;

    public const OBJETO_CONTROL = 'PG_ESTADO_CIVIL';

    protected $table = 'pg_estado_civil';

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'codigo',
        'descripcion',
        'estado',
    ];
}
