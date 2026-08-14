<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Component;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComponentSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Company::all() as $company) {
            $ratesComponents = DB::table('consumption_rates')
                ->where('company_id', $company->id)
                ->whereNotNull('component')
                ->where('component', '!=', '')
                ->pluck('component');

            $bomComponents = DB::table('bom_items')
                ->join('boms', 'boms.id', '=', 'bom_items.bom_id')
                ->where('boms.company_id', $company->id)
                ->whereNotNull('bom_items.component')
                ->where('bom_items.component', '!=', '')
                ->pluck('bom_items.component');

            $excelComponents = collect([
                'Front Upper',
                'Back Body',
                'Front Lower',
                'Strap Loop',
                'Strap',
                'Front Pocket',
                'Body',
                'Binding',
                'In Seam',
                'Binding Body',
                'Side Body',
            ]);

            $allComponents = $ratesComponents->concat($bomComponents)->concat($excelComponents)->unique();

            foreach ($allComponents as $name) {
                Component::firstOrCreate([
                    'company_id' => $company->id,
                    'name' => trim($name),
                ]);
            }
        }
    }
}
