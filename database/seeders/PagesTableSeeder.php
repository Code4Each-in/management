<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pages;

class PagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        Pages::truncate();
        Pages::insert([
            [
                'name' => "Users",
            ],
			[
                'name' => "Departments",
            ],
            [
                'name' => "Roles",
            ],
            [
                'name' => "Attendence",
            ],
            [
                'name' => "Leaves",
            ],
            [
                'name' => "Tickets",
            ],
            [
                'name' => "Projects",
            ],
        ]);
        
    }
}