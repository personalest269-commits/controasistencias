<?php
namespace Modules\Blog\Entities;

use App\Models\Concerns\EstadoSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class BlogCategories extends Model
{
    use EstadoSoftDeletes;
    protected $table = 'Blog_categories';
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
