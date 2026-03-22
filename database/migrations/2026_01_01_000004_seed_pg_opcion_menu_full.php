<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeding idempotente para pg_opcion_menu + pg_opcion_menu_rol (por id_rol).
 *
 * - Crea/actualiza los ítems base del menú (Dashboard, Gestión, Administración, etc.)
 * - Corrige tipos (route vs url) para links que no tienen ruta nombrada (ej: Translation Manager)
 * - Marca duplicados como eliminados lógicamente (estado = 'E')
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pg_opcion_menu') || !Schema::hasTable('pg_opcion_menu_rol') || !Schema::hasTable('roles')) {
            return;
        }

        // Este seed asume que la tabla ya está migrada a id_rol.
        if (!Schema::hasColumn('pg_opcion_menu_rol', 'id_rol')) {
            return;
        }

        $roleIds = array_values(array_filter([
            DB::table('roles')->where('name', 'Super-Admin')->value('id'),
            DB::table('roles')->where('name', 'Admin')->value('id'),
        ]));

        if (empty($roleIds)) {
            return;
        }

        // Helper para crear/actualizar y deduplicar por (titulo, id_padre)
        $upsertMenu = function (array $row): int {
            $q = DB::table('pg_opcion_menu')
                ->where('titulo', $row['titulo'])
                ->when(array_key_exists('id_padre', $row), fn($qq) => $qq->where('id_padre', $row['id_padre']));

            $existing = $q->orderBy('id', 'asc')->get();
            if ($existing->isEmpty()) {
                return (int) DB::table('pg_opcion_menu')->insertGetId($row);
            }

            $keepId = (int) $existing->first()->id;
            DB::table('pg_opcion_menu')->where('id', $keepId)->update($row);

            // Duplicados => estado = 'E'
            $dupIds = $existing->pluck('id')->filter(fn($id) => (int)$id !== $keepId)->values();
            if ($dupIds->isNotEmpty()) {
                DB::table('pg_opcion_menu')->whereIn('id', $dupIds->all())->update(['estado' => 'E']);
            }

            return $keepId;
        };

        // Root items
        $dashboardId = $upsertMenu([
            'titulo' => 'Dashboard',
            'id_padre' => null,
            'url' => 'dashboardIndex',
            'tipo' => 'M',
            'activo' => 'S',
            'orden' => 1,
            'id_archivo' => null,
            'estado' => null,
        ]);

        $widgetsId = $upsertMenu([
            'titulo' => 'Widgets',
            'id_padre' => null,
            'url' => 'admin/module/Widgets/1',
            'tipo' => 'G',
            'activo' => 'S',
            'orden' => 2,
            'id_archivo' => null,
            'estado' => null,
        ]);

        $facturacionId = $upsertMenu([
            'titulo' => 'Facturación',
            'id_padre' => null,
            'url' => '#',
            'tipo' => 'G',
            'activo' => 'S',
            'orden' => 15,
            'id_archivo' => null,
            'estado' => null,
        ]);

        $accountSettingsId = $upsertMenu([
            'titulo' => 'Account Settings',
            'id_padre' => null,
            'url' => '#',
            'tipo' => 'G',
            'activo' => 'S',
            'orden' => 30,
            'id_archivo' => null,
            'estado' => null,
        ]);

        $gestionId = $upsertMenu([
            'titulo' => 'Gestión',
            'id_padre' => null,
            'url' => '#',
            'tipo' => 'G',
            'activo' => 'S',
            'orden' => 40,
            'id_archivo' => null,
            'estado' => null,
        ]);

        // Administración root: aceptar tanto "Administración" como "Administracion"
        $adminRootId = (int) (DB::table('pg_opcion_menu')
            ->whereNull('id_padre')
            ->whereIn('titulo', ['Administración', 'Administracion'])
            ->orderBy('id', 'asc')
            ->value('id') ?? 0);

        if ($adminRootId === 0) {
            $adminRootId = $upsertMenu([
                'titulo' => 'Administración',
                'id_padre' => null,
                'url' => '#',
                'tipo' => 'G',
                'activo' => 'S',
                'orden' => 90,
                'id_archivo' => null,
                'estado' => null,
            ]);
        } else {
            // Asegurar formato correcto
            DB::table('pg_opcion_menu')->where('id', $adminRootId)->update([
                'titulo' => 'Administración',
                'url' => '#',
                'tipo' => 'G',
                'activo' => 'S',
                'orden' => 90,
                'estado' => null,
            ]);
        }

        // Children
        $children = [
            // Facturación
            ['titulo' => 'Invoices', 'id_padre' => $facturacionId, 'url' => 'InvoicesIndex', 'tipo' => 'M', 'orden' => 1],
            ['titulo' => 'Invoice Details', 'id_padre' => $facturacionId, 'url' => 'InvoicedetailsIndex', 'tipo' => 'M', 'orden' => 2],

            // Account Settings
            ['titulo' => 'User Profile', 'id_padre' => $accountSettingsId, 'url' => 'userprofile', 'tipo' => 'M', 'orden' => 1],
            ['titulo' => 'General Settings', 'id_padre' => $accountSettingsId, 'url' => 'general-settings', 'tipo' => 'M', 'orden' => 2],
            ['titulo' => 'Email Settings', 'id_padre' => $accountSettingsId, 'url' => 'email-settings', 'tipo' => 'M', 'orden' => 3],
            ['titulo' => 'Email Templates', 'id_padre' => $accountSettingsId, 'url' => 'email-templates', 'tipo' => 'M', 'orden' => 4],
            // Translation manager no tiene ruta nombrada => URL directa
            ['titulo' => 'Translation Manager', 'id_padre' => $accountSettingsId, 'url' => 'admin/translations', 'tipo' => 'G', 'orden' => 5],

            // Gestión
            ['titulo' => 'Personas', 'id_padre' => $gestionId, 'url' => 'PersonasIndex', 'tipo' => 'M', 'orden' => 1],
            ['titulo' => 'Archivos digitales', 'id_padre' => $gestionId, 'url' => 'ArchivosDigitalesIndex', 'tipo' => 'M', 'orden' => 2],
            ['titulo' => 'Estado civil', 'id_padre' => $gestionId, 'url' => 'EstadoCivilIndex', 'tipo' => 'M', 'orden' => 3],
            ['titulo' => 'Tipo identificación', 'id_padre' => $gestionId, 'url' => 'TipoIdentificacionIndex', 'tipo' => 'M', 'orden' => 4],

            // Administración
            ['titulo' => 'Opciones Menú', 'id_padre' => $adminRootId, 'url' => 'OpcionMenuIndex', 'tipo' => 'M', 'orden' => 1],
            ['titulo' => 'CRUD Builder', 'id_padre' => $adminRootId, 'url' => 'builder', 'tipo' => 'M', 'orden' => 2],
            ['titulo' => 'Manage Users', 'id_padre' => $adminRootId, 'url' => 'users', 'tipo' => 'M', 'orden' => 3],
            ['titulo' => 'Roles', 'id_padre' => $adminRootId, 'url' => 'roles', 'tipo' => 'M', 'orden' => 4],
            ['titulo' => 'Permissions', 'id_padre' => $adminRootId, 'url' => 'pg_permisos', 'tipo' => 'M', 'orden' => 5],
            ['titulo' => 'File Manager', 'id_padre' => $adminRootId, 'url' => 'admin/filemanage', 'tipo' => 'G', 'orden' => 6],
            ['titulo' => 'API Documentation', 'id_padre' => $adminRootId, 'url' => 'ApiDocumentationIndex', 'tipo' => 'M', 'orden' => 7],
        ];

        $allMenuIds = [$dashboardId, $widgetsId, $facturacionId, $accountSettingsId, $gestionId, $adminRootId];

        foreach ($children as $c) {
            $id = $upsertMenu([
                'titulo' => $c['titulo'],
                'id_padre' => $c['id_padre'],
                'url' => $c['url'],
                'tipo' => $c['tipo'],
                'activo' => 'S',
                'orden' => $c['orden'],
                'id_archivo' => null,
                'estado' => null,
            ]);
            $allMenuIds[] = $id;
        }

        // Deduplicado especial: "Opciones de menú" (minúsculas) => eliminar lógico si existe
        DB::table('pg_opcion_menu')
            ->where('id_padre', $adminRootId)
            ->where('titulo', 'Opciones de menú')
            ->update(['estado' => 'E']);

        // Asignar todos los ítems base a Super-Admin y Admin
        $allMenuIds = array_values(array_unique(array_map('intval', $allMenuIds)));
        foreach ($allMenuIds as $menuId) {
            foreach ($roleIds as $roleId) {
                $exists = DB::table('pg_opcion_menu_rol')
                    ->where('id_opcion_menu', $menuId)
                    ->where('id_rol', $roleId)
                    ->exists();
                if (!$exists) {
                    DB::table('pg_opcion_menu_rol')->insert([
                        'id_opcion_menu' => $menuId,
                        'id_rol' => $roleId,
                        'estado' => null,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // No-op
    }
};
