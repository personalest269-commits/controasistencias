<?php
namespace App\Models;

use App\Models\Concerns\EstadoSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class Invoicedetails extends Model
{
    use EstadoSoftDeletes;
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
    
    protected $table = 'Invoicedetails';
    public $fillable=['quantity','product','description','subtotal','invoice_id'];
    public function invoice_id(){ return $this->belongsTo('App\Models\Invoices', 'invoice_id', 'id');}
    
}
