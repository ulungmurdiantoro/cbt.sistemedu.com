<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //create user
        User::create([
            'users_code'    => 'usr-'.rand(1000000000, 9999999999),
            'name'          => 'Administrator',
            'email'         => 'admin@gmail.com',
            'password'      => bcrypt('password'),
        ]);
    }
}
