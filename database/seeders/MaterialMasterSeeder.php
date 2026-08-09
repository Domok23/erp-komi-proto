<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\MaterialUom;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MaterialMasterSeeder extends Seeder
{
    public function run(): void
    {
        $defaultCategories = [
            ['name' => 'Fabric', 'code' => 'fabric'],
            ['name' => 'Zipper', 'code' => 'zipper'],
            ['name' => 'Button', 'code' => 'button'],
            ['name' => 'Thread', 'code' => 'thread'],
            ['name' => 'Handle', 'code' => 'handle'],
            ['name' => 'Label', 'code' => 'label'],
            ['name' => 'Interlining', 'code' => 'interlining'],
            ['name' => 'Raw Material', 'code' => 'raw_material'],
            ['name' => 'Components', 'code' => 'components'],
            ['name' => 'Consumables', 'code' => 'consumables'],
            ['name' => 'Semi-Finished Product', 'code' => 'semi_finished'],
            ['name' => 'Finished Product', 'code' => 'finished'],
            ['name' => 'Other', 'code' => 'other'],
        ];

        $defaultUoms = [
            ['name' => 'pcs', 'description' => 'Pieces'],
            ['name' => 'yard', 'description' => 'Yards'],
            ['name' => 'meter', 'description' => 'Meters'],
            ['name' => 'kg', 'description' => 'Kilograms'],
            ['name' => 'roll', 'description' => 'Rolls'],
            ['name' => 'box', 'description' => 'Boxes'],
            ['name' => 'set', 'description' => 'Sets'],
            ['name' => 'pack', 'description' => 'Packs'],
        ];

        $companies = Company::all();

        foreach ($companies as $company) {
            foreach ($defaultCategories as $cat) {
                MaterialCategory::firstOrCreate([
                    'company_id' => $company->id,
                    'name' => $cat['name'],
                ], [
                    'code' => $cat['code'],
                ]);
            }

            foreach ($defaultUoms as $uom) {
                MaterialUom::firstOrCreate([
                    'company_id' => $company->id,
                    'name' => $uom['name'],
                ], [
                    'description' => $uom['description'],
                ]);
            }
        }

        // Sync existing materials category_id & uom_id
        $materials = Material::all();
        foreach ($materials as $material) {
            $companyId = $material->company_id;
            if (! $companyId) {
                $firstCompany = $companies->first();
                $companyId = $firstCompany?->id;
            }

            // Sync Category
            if ($material->category && ! $material->category_id) {
                $categoryRaw = trim($material->category);

                $categoryModel = MaterialCategory::withoutGlobalScope('company')
                    ->where('company_id', $companyId)
                    ->where(function ($q) use ($categoryRaw) {
                        $q->where('name', 'LIKE', $categoryRaw)
                            ->orWhere('code', 'LIKE', $categoryRaw)
                            ->orWhere('name', 'LIKE', Str::headline($categoryRaw));
                    })
                    ->first();

                if (! $categoryModel && $companyId) {
                    $categoryModel = MaterialCategory::create([
                        'company_id' => $companyId,
                        'name' => Str::headline($categoryRaw),
                        'code' => Str::slug($categoryRaw, '_'),
                    ]);
                }

                if ($categoryModel) {
                    $material->category_id = $categoryModel->id;
                }
            }

            // Sync UOM
            if ($material->uom && ! $material->uom_id) {
                $uomRaw = strtolower(trim($material->uom));

                // Standardize plural UOMs (yards -> yard, meters -> meter, rolls -> roll, boxes -> box, sets -> set)
                $normalizedUom = match ($uomRaw) {
                    'yards' => 'yard',
                    'meters' => 'meter',
                    'rolls' => 'roll',
                    'boxes' => 'box',
                    'sets' => 'set',
                    default => $uomRaw,
                };

                $uomModel = MaterialUom::withoutGlobalScope('company')
                    ->where('company_id', $companyId)
                    ->where('name', 'LIKE', $normalizedUom)
                    ->first();

                if (! $uomModel && $companyId) {
                    $uomModel = MaterialUom::create([
                        'company_id' => $companyId,
                        'name' => $normalizedUom,
                        'description' => ucfirst($normalizedUom),
                    ]);
                }

                if ($uomModel) {
                    $material->uom_id = $uomModel->id;
                }
            }

            $material->save();
        }
    }
}
