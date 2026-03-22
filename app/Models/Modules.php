<?php
namespace App\Models;

use App\Models\Concerns\EstadoSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class Modules extends Model
{
    use EstadoSoftDeletes;

    protected $table = 'modules';
    
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

    public function ModuleFields()
    {
        return $this->hasMany('App\ModuleFields', 'module_id', 'id');
    }
}
