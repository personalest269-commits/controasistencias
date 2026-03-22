<?php
namespace App\Models;

use App\Models\Concerns\EstadoSoftDeletes;
use App\Models\Concerns\GeneraIdVarchar;
use Shanmuga\LaravelEntrust\Models\EntrustPermission;
use DateTimeInterface;

class Permission extends EntrustPermission
{
    use EstadoSoftDeletes, GeneraIdVarchar;

    public const OBJETO_CONTROL = 'PERMISSIONS';

    // Usamos tabla prefijada "pg_".
    protected $table = 'pg_permisos';

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    
     /**
    * Prepare a date for array / JSON serialization.
    *
    * @param  \DateTimeInterface  $date
    * @return string
    */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
    
}
