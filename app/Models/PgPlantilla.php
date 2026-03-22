<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo de plantillas de UI del sistema.
 * Ej: AdminLTE, Gentelella.
 */
class PgPlantilla extends Model
{
    protected $table = 'pg_plantillas';

    protected $fillable = [
        'nombre',
        'codigo',
        'activo',
    ];
}
