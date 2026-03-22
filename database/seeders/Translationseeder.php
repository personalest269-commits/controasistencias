<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Translationseeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Only two locales are allowed in this project.
        $locales = ['en', 'es'];

        // Minimal starter set. You can expand/edit these from the Translation Manager UI.
        $base = [
            ['status' => 1, 'locale' => 'en', 'group' => 'manage_users', 'key' => 'menu_title', 'value' => 'Manage Users'],
            ['status' => 1, 'locale' => 'en', 'group' => 'file_manager', 'key' => 'menu_title', 'value' => 'File Manager'],
            ['status' => 1, 'locale' => 'en', 'group' => 'account_settings', 'key' => 'menu_title', 'value' => 'Account Settings'],
            ['status' => 1, 'locale' => 'en', 'group' => 'modules', 'key' => 'module_title', 'value' => 'Module Builder'],
            ['status' => 1, 'locale' => 'en', 'group' => 'modules', 'key' => 'module_subtitle', 'value' => 'All Modules'],
            ['status' => 1, 'locale' => 'en', 'group' => 'modules', 'key' => 'module_name', 'value' => 'Module Name'],
            ['status' => 1, 'locale' => 'en', 'group' => 'roles', 'key' => 'module_title', 'value' => 'Roles <small> Users\'s Roles </small>'],
            ['status' => 1, 'locale' => 'en', 'group' => 'permissions', 'key' => 'module_title', 'value' => 'Permissions <small>Permissions\'s List</small>'],
        ];

        // Duplicate EN rows to ES as a starter (values can be edited later).
        $es = array_map(function (array $row) {
            $row['locale'] = 'es';
            return $row;
        }, $base);

        $rows = array_merge($base, $es);
        $now = now();
        foreach ($rows as &$row) {
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
        }
        unset($row);

        // Replace only the locales we manage here.
        DB::table('ltm_translations')->whereIn('locale', $locales)->delete();
        DB::table('ltm_translations')->insert($rows);
    }
}
