<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@dimensa.com'],
            [
                'name'     => 'Administrador',
                'password' => Hash::make('password'),
            ]
        );
    }
}