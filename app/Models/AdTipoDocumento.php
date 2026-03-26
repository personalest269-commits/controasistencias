<?php

namespace App\Models;

use App\Models\Concerns\EstadoSoftDeletes;
use Illuminate\Database\Eloquent\Model;

class AdTipoDocumento extends Model
{
    use EstadoSoftDeletes;
    protected $connection = 'mysql_archivos';
    protected $table = 'ad_tipo_documento';

    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'descripcion',
        'tamano_maximo',
        'estado',
    ];
}
