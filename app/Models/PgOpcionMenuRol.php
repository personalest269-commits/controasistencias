<?php

namespace App\Models;

use App\Models\Concerns\EstadoSoftDeletes;
use App\Models\Concerns\GeneraIdVarchar;
use Illuminate\Database\Eloquent\Model;

class PgOpcionMenuRol extends Model
{
    use EstadoSoftDeletes, GeneraIdVarchar;

    public const OBJETO_CONTROL = 'PG_OPCION_MENU_ROL';

    protected $table = 'pg_opcion_menu_rol';

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id_opcion_menu',
        'id_rol',
        'estado',
    ];

    public function opcionMenu()
    {
        return $this->belongsTo(PgOpcionMenu::class, 'id_opcion_menu', 'id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'id_rol', 'id');
    }
}
