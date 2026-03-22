<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('Settings')->insert(
        [
            [
                'registration' => 1,
                'crudbuilder' => 1,
                'filemanager' => 1
            ]
            
        ]
        );
    }
}
