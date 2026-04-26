<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevAccountSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment('local', 'testing')) {
            $this->command?->info('DevAccountSeeder skipped: not in local/testing environment.');
            return;
        }

        // Admin account
        User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin1234'),
                'email_verified_at' => now(),
                'premium_expires_at' => now()->addYears(10),
                'is_admin' => true,
            ]
        );

        // Dev premium account (non-admin)
        User::firstOrCreate(
            ['email' => 'dev@test.com'],
            [
                'name' => 'Dev Premium',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'premium_expires_at' => now()->addYears(10),
            ]
        );

        // Personal verified account (admin)
        User::updateOrCreate(
            ['email' => 'zxc7370748@gmail.com'],
            [
                'name' => '碩',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'premium_expires_at' => now()->addYears(10),
                'is_admin' => true,
            ]
        );
    }
}
