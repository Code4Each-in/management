<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Modules;

class ModulesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        Modules::truncate();
        Modules::insert([
            [
                'page_id' => 1,
                'module_name' => "Listing",
            ],
            [
                'page_id' => 1,
                'module_name' => "Add",
            ],
            [
                'page_id' => 1,
                'module_name' => "Edit",
            ],
            [
                'page_id' => 1,
                'module_name' => "Delete",
            ],
            [
                'page_id' => 2,
                'module_name' => "Listing",
            ],
            [
                'page_id' => 2,
                'module_name' => "Add",
            ],
            [
                'page_id' => 2,
                'module_name' => "Edit",
            ],
            [
                'page_id' => 2,
                'module_name' => "Delete",
            ],
            [
                'page_id' => 3,
                'module_name' => "Listing",
            ],
            [
                'page_id' => 4,
                'module_name' => "Add",
            ],
            [
                'page_id' => 4,
                'module_name' => "Edit",
            ],
            [
                'page_id' => 4,
                'module_name' => "Delete",
            ],
            [
                'page_id' => 5,
                'module_name' => "Listing",
            ],
            [
                'page_id' => 5,
                'module_name' => "Add",
            ],
            [
                'page_id' => 5,
                'module_name' => "Edit",
            ],
            [
                'page_id' => 5,
                'module_name' => "Delete",
            ],
            [
                'page_id' => 6,
                'module_name' => "Listing",
            ],
            [
                'page_id' => 6,
                'module_name' => "Add",
            ],
            [
                'page_id' => 6,
                'module_name' => "Edit",
            ],
            [
                'page_id' => 6,
                'module_name' => "Delete",
            ],
        ]);
        
    }
}