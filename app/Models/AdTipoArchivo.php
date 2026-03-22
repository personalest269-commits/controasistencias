<?php

namespace App\Models;

use App\Models\Concerns\EstadoSoftDeletes;
use Illuminate\Database\Eloquent\Model;

class AdTipoArchivo extends Model
{
    use EstadoSoftDeletes;
    protected $table = 'ad_tipo_archivo';

    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'descripcion',
        'tipo_mime',
        'extension',
        'estado',
    ];
}
