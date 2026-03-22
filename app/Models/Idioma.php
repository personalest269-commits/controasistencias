<?php

namespace App\Models;

use App\Models\Concerns\EstadoSoftDeletes;
use App\Models\Concerns\GeneraIdVarchar;
use Illuminate\Database\Eloquent\Model;

class Idioma extends Model
{
    use EstadoSoftDeletes, GeneraIdVarchar;

    public const OBJETO_CONTROL = 'IDIOMAS';
    protected $table = 'pg_idiomas';

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'codigo',
        'nombre',
        'activo',
        'por_defecto',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'por_defecto' => 'boolean',
    ];
}
