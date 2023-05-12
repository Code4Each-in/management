<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Departments;

class DepartmentTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Departments::truncate();
        Departments::insert([
            [
                'name' => "Sr. Web Developer",
            ],
            [
                'name' => "IT",
            ],
            [
                'name' => "HR",
            ],
            [
                'name' => "Developer",
            ],
            [
                'name' => "Tester",
            ]

        ]);
    }
}
