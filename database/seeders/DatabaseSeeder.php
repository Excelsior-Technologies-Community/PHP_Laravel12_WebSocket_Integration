<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Default guest user create karo
        User::firstOrCreate(
            ['email' => 'guest@chat.com'],
            [
                'name'     => 'Guest',
                'email'    => 'guest@chat.com',
                'password' => bcrypt('password'),
            ]
        );
    }
}