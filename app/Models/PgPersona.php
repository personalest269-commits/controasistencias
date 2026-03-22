<?php

namespace App\Models;

use App\Models\Concerns\EstadoSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PgPersona extends Model
{
    // Alias para poder extender el delete() y seguir usando la lógica de EstadoSoftDeletes
    use EstadoSoftDeletes {
        delete as estadoSoftDelete;
    }

    protected $table = 'pg_persona';

    /**
     * PK string (no autoincrement)
     */
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * La tabla original no tiene created_at/updated_at.
     */
    public $timestamps = false;

    protected $fillable = [
        'id',
        'tipo',
        'nombres',
        'apellido1',
        'apellido2',
        'direccion',
        'fecha_nacimiento',
        'tipo_identificacion',
        'identificacion',
        'sexo',
        'celular',
        'email',
        'departamento_id',
        'cod_estado_civil',
        'fecha_ingreso',
        'estado',
    ];

    public function departamento()
    {
        return $this->belongsTo(PgDepartamento::class, 'departamento_id', 'id');
    }

    public function fotos()
    {
        return $this->hasMany(PgPersonaFoto::class, 'id_persona', 'id');
    }

    public function fotoActual()
    {
        return $this->hasOne(PgPersonaFoto::class, 'id_persona', 'id')->latestOfMany();
    }

    public function usuarios()
    {
        return $this->hasMany(User::class, 'id_persona', 'id');
    }

    /**
     * Eliminación lógica en cascada:
     * Si una persona se elimina (estado='X'), también se eliminan lógicamente
     * sus usuarios asociados (pg_usuario.estado='X') y se limpian sus roles.
     */
    public function delete(): bool
    {
        try {
            // Incluir también usuarios ya eliminados (por si se llama delete() varias veces)
            $usuarios = $this->usuarios()->conEliminados()->get();
            foreach ($usuarios as $u) {
                try {
                    $u->delete();
                } catch (\Throwable $e) {
                    // ignore
                }

                // Limpieza de pivot de roles (role_user) para evitar basura
                try {
                    DB::table('role_user')->where('usuario_id', $u->id)->delete();
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return $this->estadoSoftDelete();
    }

    public function getNombreCompletoAttribute(): string
    {
        $parts = array_filter([
            trim((string) $this->nombres),
            trim((string) $this->apellido1),
            trim((string) $this->apellido2),
        ]);
        return trim(implode(' ', $parts));
    }

    /**
     * Compatibilidad: versiones anteriores guardaban letras (C/R/P...) en tipo_identificacion.
     * Ahora se guarda el código del catálogo pg_tipo_identificacion (1..8).
     */
    public function getTipoIdentificacionNormalizadoAttribute(): ?string
    {
        $v = trim((string) $this->tipo_identificacion);
        if ($v === '') {
            return null;
        }

        $map = [
            'C' => '2', // Cédula
            'R' => '1', // RUC
            'P' => '3', // Pasaporte
        ];

        return $map[$v] ?? $v;
    }
}
