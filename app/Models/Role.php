<?php
namespace App\Models;

use App\Models\Concerns\EstadoSoftDeletes;
use App\Models\Concerns\GeneraIdVarchar;
use Shanmuga\LaravelEntrust\Models\EntrustRole;
use DateTimeInterface;

class Role extends EntrustRole
{
    use EstadoSoftDeletes, GeneraIdVarchar;

    public const OBJETO_CONTROL = 'ROLES';

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
