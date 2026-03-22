<?php

namespace App\Models;

use App\Models\Concerns\EstadoSoftDeletes;
use App\Models\Concerns\GeneraIdVarchar;
use Illuminate\Database\Eloquent\Model;

class PgPersonaFoto extends Model
{
    use EstadoSoftDeletes, GeneraIdVarchar;

    public const OBJETO_CONTROL = 'PG_PERSONA_FOTO';

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'pg_persona_foto';

    protected $fillable = [
        'id_persona',
        'id_archivo',
        'estado',
    ];

    public function persona()
    {
        return $this->belongsTo(PgPersona::class, 'id_persona', 'id');
    }

    public function archivo()
    {
        return $this->belongsTo(AdArchivoDigital::class, 'id_archivo', 'id');
    }
}
