<?php

namespace App\Models;

use App\Models\Concerns\EstadoSoftDeletes;
use App\Models\Concerns\GeneraIdVarchar;
use Illuminate\Database\Eloquent\Model;

class PgOpcionMenu extends Model
{
    use EstadoSoftDeletes, GeneraIdVarchar;

    public const OBJETO_CONTROL = 'PG_OPCION_MENU';

    protected $table = 'pg_opcion_menu';

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    // Esta tabla no usa created_at/updated_at
    public $timestamps = false;

    protected $fillable = [
        'titulo',
        'id_padre',
        'url',
        'tipo',
        'activo',
        'orden',
        'id_archivo',
        'estado',
    ];

    public function padre()
    {
        return $this->belongsTo(PgOpcionMenu::class, 'id_padre', 'id');
    }

    public function hijos()
    {
        return $this->hasMany(PgOpcionMenu::class, 'id_padre', 'id')->orderBy('orden');
    }

    public function roles()
    {
        return $this->hasMany(PgOpcionMenuRol::class, 'id_opcion_menu', 'id');
    }

    public function archivo()
    {
        return $this->belongsTo(AdArchivoDigital::class, 'id_archivo', 'id');
    }
}
