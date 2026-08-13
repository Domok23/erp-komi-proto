<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CompanySeeder::class,
            AdminUserSeeder::class,
            DataSeeder::class,
            MaterialMasterSeeder::class,
            ComponentSeeder::class,
            Phase2Seeder::class,
            DeliveryAlertSeeder::class,
            HrModuleSeeder::class,
            ChartOfAccountSeeder::class,
            GeneralLedgerSeeder::class,
        ]);
    }
}
