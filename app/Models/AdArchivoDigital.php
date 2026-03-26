<?php

namespace App\Models;

use App\Models\Concerns\GeneraIdVarchar;
use Illuminate\Database\Eloquent\Model;

class AdArchivoDigital extends Model
{
    use GeneraIdVarchar;

    public const OBJETO_CONTROL = 'AD_ARCHIVO_DIGITAL';

    protected $connection = 'mysql_archivos';
    protected $table = 'ad_archivo_digital';

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'tipo_documento_codigo',
        'tipo_archivo_codigo',
        'nombre_original',
        'ruta',
        'digital',
        'tipo_mime',
        'extension',
        'tamano',
        'descripcion',
        'estado',
    ];

    public function tipoDocumento()
    {
        return $this->belongsTo(AdTipoDocumento::class, 'tipo_documento_codigo', 'codigo');
    }

    public function tipoArchivo()
    {
        return $this->belongsTo(AdTipoArchivo::class, 'tipo_archivo_codigo', 'codigo');
    }

    public function scopeActivos($query)
    {
        return $query->whereNull('estado');
    }

    public function scopeEliminados($query)
    {
        return $query->where('estado', 'X');
    }
}
