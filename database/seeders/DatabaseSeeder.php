<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Database\Seeders\FieldsSeeder;
use Database\Seeders\RolesSeeder;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\PermissionRoleSeeder;
use Database\Seeders\SettingsSeeder;
use Database\Seeders\Translationseeder;
use Database\Seeders\ApiDocumentationSeeder;
use Modules\Blog\Database\Seeders\BlogDatabaseSeeder;

class DatabaseSeeder extends Seeder {

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run() {
        $this->call(FieldsSeeder::class);
        $this->call(RolesSeeder::class);
        $this->call(PermissionsSeeder::class);
        $this->call(PermissionRoleSeeder::class);
        $this->call(SettingsSeeder::class);
        $this->call(Translationseeder::class);
        $this->call(ApiDocumentationSeeder::class);
        $this->call(BlogDatabaseSeeder::class);
        //$this->call(RoleUserSeeder::class);
    }

}
