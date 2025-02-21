<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class PisaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = json_decode(file_get_contents(__DIR__ . '/data/students.json'), true);

        foreach ($students as $st) {
            User::updateOrCreate([
                'citizen_id' => $st['citizen_id'],
            ], [
                'first_name' => $st['first_name'],
                'last_name' => $st['last_name'],
                'grade' => $st['grade'],
                'class_name' => $st['class_name'],
                'school_id' => $st['school_id'],
            ]);
        }
    }
}
