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
        // 1. Akun Admin Utama
        User::updateOrCreate(
            ['email' => 'admin@mapos.test'],
            [
                'name' => 'Administrator MaPOS',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ]
        );

        // 2. Akun Kasir 1: Choerul Nuril Huda
        User::updateOrCreate(
            ['email' => 'choerul@mapos.test'],
            [
                'name' => 'Choerul Nuril Huda',
                'password' => Hash::make('password'),
                'role' => UserRole::Kasir,
                'email_verified_at' => now(),
            ]
        );

        // 3. Akun Kasir 2: Raihaan Adian
        User::updateOrCreate(
            ['email' => 'raihaan@mapos.test'],
            [
                'name' => 'Raihaan Adian',
                'password' => Hash::make('password'),
                'role' => UserRole::Kasir,
                'email_verified_at' => now(),
            ]
        );
    }
}
