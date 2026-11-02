<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (!$email || !$password) {
            $this->command?->warn('ADMIN_EMAIL/ADMIN_PASSWORD not set; skipping admin seed.');
            return;
        }

        User::firstOrCreate(
            ['email' => $email],
            [
                'nickname' => 'admin',
                'password' => Hash::make($password),
                'rol' => 'admin',
                'email_verified_at' => now(),
            ]
        );
    }
}