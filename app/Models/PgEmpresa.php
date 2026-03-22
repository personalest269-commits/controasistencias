<?php

namespace App\Models;

use App\Models\Concerns\EstadoSoftDeletes;
use App\Models\Concerns\GeneraIdVarchar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PgEmpresa extends Model
{
    use HasFactory, EstadoSoftDeletes, GeneraIdVarchar;

    protected $table = 'pg_empresa';

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    public const OBJETO_CONTROL = 'PG_EMPRESA';

    protected $fillable = [
        'id',
        'nombre',
        'ruc',
        'direccion',
        'telefono',
        'correo',
        'estado', // NULL activo, 'X' eliminado lógico
    ];

    public function departamentos()
    {
        return $this->hasMany(PgDepartamento::class, 'empresa_id', 'id');
    }
}
