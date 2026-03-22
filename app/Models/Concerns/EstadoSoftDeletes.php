<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Eliminación lógica basada en el campo `estado`:
 * - NULL = activo
 * - 'X'  = eliminado
 */
trait EstadoSoftDeletes
{
    /**
     * Aplica un global scope para traer solo registros activos (estado IS NULL).
     */
    protected static function bootEstadoSoftDeletes(): void
    {
        static::addGlobalScope('estado_activo', function (Builder $builder) {
            /** @var Model $model */
            $model = $builder->getModel();
            // Evitar errores si alguna tabla aún no tiene la columna.
            // Importante: en algunas BD el "activo" puede estar como NULL o como 'A' / '' (dependiendo del legado).
            // Aquí tratamos como "eliminado" únicamente a 'X' y consideramos activo todo lo demás.
            try {
                if (!Schema::hasColumn($model->getTable(), 'estado')) {
                    return;
                }

                $col = $model->getTable() . '.estado';
                $builder->where(function (Builder $q) use ($col) {
                    $q->whereNull($col)->orWhere($col, '<>', 'X');
                });
            } catch (\Throwable $e) {
                // no-op
            }
        });
    }

    /**
     * Scope para incluir eliminados.
     */
    public function scopeConEliminados(Builder $query): Builder
    {
        return $query->withoutGlobalScope('estado_activo');
    }

    /**
     * Scope: solo eliminados.
     */
    public function scopeSoloEliminados(Builder $query): Builder
    {
        return $query->withoutGlobalScope('estado_activo')->where('estado', 'X');
    }

    /**
     * Scope: solo activos.
     */
    public function scopeSoloActivos(Builder $query): Builder
    {
        return $query->withoutGlobalScope('estado_activo')->whereNull('estado');
    }

    /**
     * Marca como eliminado lógico.
     */
    public function delete(): bool
    {
        // Si ya está eliminado, no hacemos nada.
        if ($this->getAttribute('estado') === 'X') {
            return true;
        }

        $this->setAttribute('estado', 'X');

        // Usamos saveQuietly si existe (Laravel 8+)
        if (method_exists($this, 'saveQuietly')) {
            return (bool) $this->saveQuietly();
        }

        return (bool) $this->save();
    }

    /**
     * Elimina físicamente (si lo necesitas en casos puntuales).
     */
    public function forceDelete(): bool
    {
        return (bool) parent::delete();
    }
}
