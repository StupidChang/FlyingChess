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

        User::firstOrCreate(
            ['email' => 'dev@test.com'],
            [
                'name' => 'Dev Premium',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'premium_expires_at' => now()->addYears(10),
            ]
        );
    }
}
