<?php

namespace App\Models;

use App\Models\Concerns\EstadoSoftDeletes;
use App\Models\Concerns\GeneraIdVarchar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PgEvento extends Model
{
    use HasFactory, EstadoSoftDeletes, GeneraIdVarchar;

    protected $table = 'pg_eventos';

    public $incrementing = false;
    protected $keyType = 'string';

    public const OBJETO_CONTROL = 'PG_EVENTOS';

    protected $fillable = [
        'id',
        'departamento_id',
        'persona_id',
        'titulo',
        'fecha_inicio',
        'fecha_fin',
        'color',
        'descripcion',
        'estado',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    public function personas()
    {
        return $this->belongsToMany(PgPersona::class, 'pg_evento_persona', 'evento_id', 'persona_id')
            ->wherePivot(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->withTimestamps();
    }

    public function departamentos()
    {
        return $this->belongsToMany(PgDepartamento::class, 'pg_evento_departamento', 'evento_id', 'departamento_id')
            ->wherePivot(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->withTimestamps();
    }
}
