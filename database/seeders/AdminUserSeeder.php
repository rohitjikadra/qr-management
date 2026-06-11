<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => 'ChangeMe@123',
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->forceFill(['role' => 'super_admin'])->save();

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => 'ChangeMe@123',
                'email_verified_at' => now(),
            ]
        );
        $admin->forceFill(['role' => 'admin'])->save();
    }
}
