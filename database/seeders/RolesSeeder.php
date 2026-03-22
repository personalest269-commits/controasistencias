<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Super-Admin',
                'display_name' => 'Super Admin',
                'description' => 'Super Admin',
            ],
            [
                'name' => 'Admin',
                'display_name' => 'Admin Role',
                'description' => 'This is Admin Role',
            ],
        ];

        // Si la tabla tiene timestamps, los agrega sin romper si no existen
        if (Schema::hasColumn('roles', 'created_at')) {
            $now = now();
            foreach ($roles as &$r) {
                $r['created_at'] = $r['created_at'] ?? $now;
                $r['updated_at'] = $now;
            }
            unset($r);

            DB::table('roles')->upsert(
                $roles,
                ['name'],
                ['display_name', 'description', 'updated_at']
            );
        } else {
            DB::table('roles')->upsert(
                $roles,
                ['name'],
                ['display_name', 'description']
            );
        }
    }
}
