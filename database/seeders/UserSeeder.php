<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'alice@example.com'],
            ['name' => 'Alice', 'password' => bcrypt('secret')]
        );

        User::updateOrCreate(
            ['email' => 'bob@example.com'],
            ['name' => 'Bob', 'password' => bcrypt('secret')]
        );

        User::factory()->count(3)->create();
    }
}