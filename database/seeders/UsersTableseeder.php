<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Users;
use Illuminate\Support\Facades\Hash;

class UsersTableseeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $user = new Users();
        $user->first_name = 'admin';
        $user->last_name = 'admin';
        $user->email='admin@gmail.com';
        $user->password = 'admin';
        $user->role_id =1;
        $user->save();
        
    }
}