<?php

namespace App\Models;

use App\Models\Concerns\EstadoSoftDeletes;
use App\Models\Concerns\GeneraIdVarchar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PgDepartamento extends Model
{
    use HasFactory, EstadoSoftDeletes, GeneraIdVarchar;

    protected $table = 'pg_departamento';

    public $incrementing = false;
    protected $keyType = 'string';

    public const OBJETO_CONTROL = 'PG_DEPARTAMENTO';

    protected $fillable = [
        'id',
        'empresa_id',
        'codigo',
        'descripcion',
        'cod_padre',
        'cod_programa',
        'ultimo_nivel',
        'vigencia_desde',
        'vigencia_hasta',
        'id_jefe',
        'identificador_activo_fijo',
        'extension_telefonica',
        'cod_clasificacion_departamento',
        'estado',
    ];

    public function empresa()
    {
        return $this->belongsTo(PgEmpresa::class, 'empresa_id', 'id');
    }

    public function jefe()
    {
        return $this->belongsTo(PgPersona::class, 'id_jefe', 'id');
    }

    public function eventos()
    {
        return $this->belongsToMany(PgEvento::class, 'pg_evento_departamento', 'departamento_id', 'evento_id')
            ->withTimestamps();
    }
}
