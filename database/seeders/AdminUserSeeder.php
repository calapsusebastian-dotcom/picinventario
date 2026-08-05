<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'calapsusebastian@gmail.com'],
            [
                'name' => 'Sebastian Calapsu',
                'password' => Hash::make('Damocles1/('),
                'roles' => ['admin'],
                'email_verified_at' => now(),
            ]
        );
    }
}
