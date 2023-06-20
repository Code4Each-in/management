<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        $this->call(UsersTableseeder::class);
        $this->call(ModulesTableSeeder::class);
        $this->call(PagesTableSeeder::class);
        $this->call(RolesTableSeeder::class);
        $this->call(DepartmentTableSeeder::class);

    }
}
