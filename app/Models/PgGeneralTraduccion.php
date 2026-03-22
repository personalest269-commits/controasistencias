<?php

namespace App\Models;

use App\Models\Concerns\EstadoSoftDeletes;
use App\Models\Concerns\GeneraIdVarchar;
use Illuminate\Database\Eloquent\Model;

/**
 * Traducciones generales del sistema.
 *
 * PK: id (VARCHAR(10) con padding 0000000001)
 */
class PgGeneralTraduccion extends Model
{
    use EstadoSoftDeletes, GeneraIdVarchar;

    public const OBJETO_CONTROL = 'PG_GENERAL_TRADUCCION';
    protected $table = 'pg_general_traduccion';

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'clave',
        'idioma_codigo',
        'texto',
        'estado',
    ];
}
