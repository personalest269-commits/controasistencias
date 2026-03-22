<?php
namespace Modules\Blog\Entities;

use App\Models\Concerns\EstadoSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class Blog extends Model
{
    use EstadoSoftDeletes;
    protected $table = 'Blog';
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
    
    public function category(){
        return $this->belongsTo('Modules\Blog\Entities\BlogCategories', 'category', 'id');
    }
    
}
