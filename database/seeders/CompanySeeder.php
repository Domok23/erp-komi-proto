<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Seed the companies.
     */
    public function run(): void
    {
        Company::updateOrCreate(
            ['code' => 'KEI'],
            [
                'name' => 'PT Komitrando Emporio',
                'code' => 'KEI',
                'type' => 'main',
                'address' => 'Jl. Industri Raya No. 10, Bandung',
                'phone' => '022-1234567',
            ]
        );

        Company::updateOrCreate(
            ['code' => 'KTK'],
            [
                'name' => 'PT Komitrando Textile',
                'code' => 'KTK',
                'type' => 'branch',
                'address' => 'Jl. Usaha Raya No. 5, Bandung',
                'phone' => '022-7654321',
            ]
        );
    }
}