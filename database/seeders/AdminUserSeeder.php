<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the super admin user.
     */
    public function run(): void
    {
        $company = \App\Models\Company::firstOrFail();

        User::updateOrCreate(
            ['email' => 'admin@komi.com'],
            [
                'name' => 'Admin',
                'email' => 'admin@komi.com',
                'password' => 'password',
                'role' => 'admin',
                'company_id' => $company->id,
            ]
        );
    }
}