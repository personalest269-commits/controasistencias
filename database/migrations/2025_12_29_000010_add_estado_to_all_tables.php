<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Añade la columna `estado` (NULL = activo / 'X' = eliminado) a todas las tablas (incluye tablas del framework).
        // En caso de que ya exista, se omite.
        $database = DB::getDatabaseName();
        $rows = DB::select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');

        // La columna del resultado puede variar según el motor, tomamos el primer valor.
        $tables = [];
        foreach ($rows as $row) {
            $arr = (array) $row;
            $tableName = array_values($arr)[0] ?? null;
            if ($tableName) {
                $tables[] = $tableName;
            }
        }

        foreach ($tables as $tableName) {
            try {
                if (!Schema::hasColumn($tableName, 'estado')) {
                    Schema::table($tableName, function (Blueprint $blueprint) {
                        $blueprint->char('estado', 1)->nullable()->default(null);
                        $blueprint->index('estado');
                    });
                }
            } catch (\Throwable $e) {
                // No detenemos la migración por una tabla problemática (por ejemplo, vistas o privilegios).
            }
        }
    }

    public function down(): void
    {
        $rows = DB::select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
        $tables = [];
        foreach ($rows as $row) {
            $arr = (array) $row;
            $tableName = array_values($arr)[0] ?? null;
            if ($tableName) {
                $tables[] = $tableName;
            }
        }

        foreach ($tables as $tableName) {
            try {
                if (Schema::hasColumn($tableName, 'estado')) {
                    Schema::table($tableName, function (Blueprint $blueprint) use ($tableName) {
                        // Drop index si existe
                        try {
                            $blueprint->dropIndex($tableName . '_estado_index');
                        } catch (\Throwable $e) {
                            // no-op
                        }
                        try {
                            $blueprint->dropColumn('estado');
                        } catch (\Throwable $e) {
                            // no-op
                        }
                    });
                }
            } catch (\Throwable $e) {
                // no-op
            }
        }
    }
};
