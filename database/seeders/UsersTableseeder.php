<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Users;
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
        $user->password = bcrypt('admin');
        $user->phone='9876543223';
        $user->address='mohali';
        $user->save();
        
    }
}