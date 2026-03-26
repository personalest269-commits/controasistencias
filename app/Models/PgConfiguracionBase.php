<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PgConfiguracionBase extends Model
{
    protected $table = 'pg_configuraciones_bases';

    protected $fillable = [
        'nombre',
        'driver',
        'host',
        'port',
        'database',
        'schema',
        'username',
        'password',
        'charset',
        'collation',
        'activo',
        'estado',
        'descripcion',
    ];
}
