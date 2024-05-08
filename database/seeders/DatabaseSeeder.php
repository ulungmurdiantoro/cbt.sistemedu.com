<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Student;
use App\Models\Report;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'users_code'    => 'usr-' . rand(11, 99) . uniqid(),
            'name'          => 'Administrator',
            'email'         => 'admin@gmail.com',
            'password'      => bcrypt('password'),
        ]);

        Classroom::factory()->create([
            'classrooms_code'   => 'clsr-' . rand(11, 99) . uniqid(),
            'title'             => 'LEAD AUDITOR INTERNAL',
        ]);
    }
}
