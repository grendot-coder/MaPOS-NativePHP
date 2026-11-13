<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Akun Admin
        User::updateOrCreate(
            ['email' => 'admin@mapos.test'],
            [
                'name' => 'Administrator MaPOS',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ]
        );

        // 2. Akun Kasir
        User::updateOrCreate(
            ['email' => 'kasir@mapos.test'],
            [
                'name' => 'Kasir Utama MaPOS',
                'password' => Hash::make('password'),
                'role' => UserRole::Kasir,
                'email_verified_at' => now(),
            ]
        );
    }
}
