<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $targetConnection = 'mysql_archivos';
        $sourceConnection = 'mysql';

        $targetConfig = config("database.connections.{$targetConnection}", []);
        if (($targetConfig['driver'] ?? null) !== 'mysql') {
            return;
        }

        $targetDatabase = (string) ($targetConfig['database'] ?? '');
        if ($targetDatabase === '') {
            return;
        }

        if (!preg_match('/^[A-Za-z0-9_]+$/', $targetDatabase)) {
            throw new RuntimeException('ARCHIVOS_DB_DATABASE contiene caracteres inválidos.');
        }

        DB::statement("CREATE DATABASE IF NOT EXISTS `{$targetDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $this->createTablesIfNeeded($targetConnection);

        if (!config("database.connections.{$sourceConnection}")) {
            return;
        }

        $sourceDatabase = (string) (config("database.connections.{$sourceConnection}.database") ?? '');
        if ($sourceDatabase !== '' && strcasecmp($sourceDatabase, $targetDatabase) === 0) {
            return;
        }

        $this->copyCatalogTable($sourceConnection, $targetConnection, 'ad_tipo_archivo', 'codigo', [
            'codigo', 'descripcion', 'tipo_mime', 'extension', 'estado',
        ]);

        $this->copyCatalogTable($sourceConnection, $targetConnection, 'ad_tipo_documento', 'codigo', [
            'codigo', 'descripcion', 'tamano_maximo', 'estado',
        ]);

        $this->copyCatalogTable($sourceConnection, $targetConnection, 'ad_archivo_digital', 'id', [
            'id', 'tipo_documento_codigo', 'tipo_archivo_codigo', 'nombre_original', 'ruta', 'digital',
            'tipo_mime', 'extension', 'tamano', 'descripcion', 'estado', 'created_at', 'updated_at',
        ], true);
    }

    public function down(): void
    {
        // No se eliminan tablas ni datos migrados para evitar pérdida de archivos.
    }

    private function createTablesIfNeeded(string $connection): void
    {
        if (!Schema::connection($connection)->hasTable('ad_tipo_archivo')) {
            Schema::connection($connection)->create('ad_tipo_archivo', function (Blueprint $table) {
                $table->string('codigo', 5)->primary();
                $table->string('descripcion', 40);
                $table->string('tipo_mime', 255)->nullable();
                $table->string('extension', 10)->nullable();
                $table->char('estado', 1)->nullable()->index();
            });
        }

        if (!Schema::connection($connection)->hasTable('ad_tipo_documento')) {
            Schema::connection($connection)->create('ad_tipo_documento', function (Blueprint $table) {
                $table->string('codigo', 5)->primary();
                $table->string('descripcion', 50);
                $table->integer('tamano_maximo');
                $table->char('estado', 1)->nullable()->index();
            });
        }

        if (!Schema::connection($connection)->hasTable('ad_archivo_digital')) {
            Schema::connection($connection)->create('ad_archivo_digital', function (Blueprint $table) {
                $table->string('id', 10)->primary();
                $table->string('tipo_documento_codigo', 5)->nullable()->index();
                $table->string('tipo_archivo_codigo', 5)->nullable()->index();
                $table->string('nombre_original', 255);
                $table->string('ruta', 600);
                $table->longText('digital')->nullable();
                $table->string('tipo_mime', 255);
                $table->string('extension', 10);
                $table->integer('tamano');
                $table->string('descripcion', 255)->nullable();
                $table->char('estado', 1)->nullable()->index();
                $table->timestamps();
            });
        }
    }

    private function copyCatalogTable(
        string $sourceConnection,
        string $targetConnection,
        string $table,
        string $uniqueBy,
        array $columns,
        bool $largePayload = false
    ): void {
        if (!Schema::connection($sourceConnection)->hasTable($table) || !Schema::connection($targetConnection)->hasTable($table)) {
            return;
        }

        $chunkSize = $largePayload ? 20 : 300;

        DB::connection($sourceConnection)
            ->table($table)
            ->orderBy($uniqueBy)
            ->chunkById($chunkSize, function ($rows) use ($targetConnection, $table, $uniqueBy, $columns, $largePayload) {
                $payload = [];
                foreach ($rows as $row) {
                    $item = [];
                    foreach ($columns as $column) {
                        $item[$column] = $row->{$column} ?? null;
                    }
                    $payload[] = $item;
                }

                if (empty($payload)) {
                    return;
                }

                if ($largePayload) {
                    // Evita consultas ON DUPLICATE KEY enormes cuando `digital` contiene blobs/base64 grandes.
                    foreach ($payload as $row) {
                        $targetTable = DB::connection($targetConnection)->table($table);
                        $exists = $targetTable->where($uniqueBy, $row[$uniqueBy])->exists();
                        if ($exists) {
                            DB::connection($targetConnection)
                                ->table($table)
                                ->where($uniqueBy, $row[$uniqueBy])
                                ->update($row);
                        } else {
                            DB::connection($targetConnection)->table($table)->insert($row);
                        }
                    }
                } else {
                    DB::connection($targetConnection)->table($table)->upsert($payload, [$uniqueBy], $columns);
                }
            }, $uniqueBy);
    }
};
