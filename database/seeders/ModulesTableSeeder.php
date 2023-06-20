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
                'route_name' => "users.index",
            ],
            [
                'page_id' => 1,
                'module_name' => "Add",
                'route_name' => "users.add",
            ],
            [
                'page_id' => 1,
                'module_name' => "Edit",
                'route_name' => "users.edit",
            ],
            [
                'page_id' => 1,
                'module_name' => "Delete",
                'route_name' => "users.delete",

            ],
            [
                'page_id' => 2,
                'module_name' => "Listing",
                'route_name' => "departments.index",

            ],
            [
                'page_id' => 2,
                'module_name' => "Add",
                'route_name' => "departments.add",
            ],
            [
                'page_id' => 2,
                'module_name' => "Edit",
                'route_name' => "departments.edit",
            ],
            [
                'page_id' => 2,
                'module_name' => "Delete",
                'route_name' => "departments.delete",
            ],
            [
                'page_id' => 3,
                'module_name' => "Listing",
                'route_name' => "roles.index",
            ],
            [
                'page_id' => 3,
                'module_name' => "Add",
                'route_name' => "roles.add",
            ],
            [
                'page_id' => 3,
                'module_name' => "Edit",
                'route_name' => "roles.edit",
            ],
            [
                'page_id' => 3,
                'module_name' => "Delete",
                'route_name' => "roles.delete",
            ],
            [
                'page_id' => 4,
                'module_name' => "Listing",
                'route_name' => "attendance.index",
            ],
            [
                'page_id' => 4,
                'module_name' => "Add",
                'route_name' => "attendance.add",
            ],
            [
                'page_id' => 4,
                'module_name' => "Edit",
                'route_name' => "attendance.edit",
            ],
            [
                'page_id' => 4,
                'module_name' => "Delete",
                'route_name' => "attendance.delete",
            ],
            [
                'page_id' => 5,
                'module_name' => "Listing",
                'route_name' => "leaves.index",
            ],
            [
                'page_id' => 5,
                'module_name' => "Add",
                'route_name' => "leaves.add",
            ],
            [
                'page_id' => 5,
                'module_name' => "Edit",
                'route_name' => "leaves.edit",
            ],
            [
                'page_id' => 5,
                'module_name' => "Delete",
                'route_name' => "leaves.delete",
            ],
            [
                'page_id' => 6,
                'module_name' => "Listing",
                'route_name' => "tickets.index",
            ],
            [
                'page_id' => 6,
                'module_name' => "Add",
                'route_name' => "tickets.add",
            ],
            [
                'page_id' => 6,
                'module_name' => "Edit",
                'route_name' => "tickets.edit",
            ],
            [
                'page_id' => 6,
                'module_name' => "Delete",
                'route_name' => "tickets.delete",
            ],
            [
                'page_id' => 7,
                'module_name' => "Listing",
                'route_name' => "projects.index",
            ],
            [
                'page_id' => 7,
                'module_name' => "Add",
                'route_name' => "projects.add",
            ],
            [
                'page_id' => 7,
                'module_name' => "Edit",
                'route_name' => "projects.edit",
            ],
            [
                'page_id' => 7,
                'module_name' => "Delete",
                'route_name' => "projects.delete",
            ],
        ]);
        
    }
}