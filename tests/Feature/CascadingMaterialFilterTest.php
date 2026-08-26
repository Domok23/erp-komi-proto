<?php

namespace Tests\Feature;

use App\Filament\Support\MaterialFormFilterHelper;
use App\Models\BomItem;
use App\Models\Company;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\MerchandisePlanningItem;
use App\Models\Supplier;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CascadingMaterialFilterTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'PT Test Komitrando',
            'code' => 'KOMITEST',
            'is_active' => true,
        ]);

        CompanyContext::setCompany($this->company);
    }

    public function test_apply_filters_filters_by_category_and_supplier(): void
    {
        $catFabric = MaterialCategory::create(['name' => 'Fabric', 'code' => 'FAB', 'company_id' => $this->company->id]);
        $catZipper = MaterialCategory::create(['name' => 'Zipper', 'code' => 'ZIP', 'company_id' => $this->company->id]);

        $supA = Supplier::create(['name' => 'Supplier A', 'code' => 'SUP-A', 'company_id' => $this->company->id]);
        $supB = Supplier::create(['name' => 'Supplier B', 'code' => 'SUP-B', 'company_id' => $this->company->id]);

        $mat1 = Material::create(['code' => 'M1', 'name' => 'Fabric A', 'category' => 'fabric', 'category_id' => $catFabric->id, 'supplier_id' => $supA->id, 'company_id' => $this->company->id]);
        $mat2 = Material::create(['code' => 'M2', 'name' => 'Fabric B', 'category' => 'fabric', 'category_id' => $catFabric->id, 'supplier_id' => $supB->id, 'company_id' => $this->company->id]);
        $mat3 = Material::create(['code' => 'M3', 'name' => 'Zipper A', 'category' => 'zipper', 'category_id' => $catZipper->id, 'supplier_id' => $supA->id, 'company_id' => $this->company->id]);

        // 1. No filters -> returns all 3
        $getEmpty = fn ($key) => null;
        $results = MaterialFormFilterHelper::applyFilters(Material::query(), $getEmpty)->get();
        $this->assertCount(3, $results);

        // 2. Category filter only (Fabric) -> returns 2
        $getCategory = fn ($key) => $key === 'filter_category_id' ? $catFabric->id : null;
        $results = MaterialFormFilterHelper::applyFilters(Material::query(), $getCategory)->get();
        $this->assertCount(2, $results);
        $this->assertTrue($results->contains($mat1));
        $this->assertTrue($results->contains($mat2));
        $this->assertFalse($results->contains($mat3));

        // 3. Supplier filter only (Supplier A) -> returns 2
        $getSupplier = fn ($key) => $key === 'filter_supplier_id' ? $supA->id : null;
        $results = MaterialFormFilterHelper::applyFilters(Material::query(), $getSupplier)->get();
        $this->assertCount(2, $results);
        $this->assertTrue($results->contains($mat1));
        $this->assertTrue($results->contains($mat3));
        $this->assertFalse($results->contains($mat2));

        // 4. Both filters (Fabric + Supplier A) -> returns 1
        $getBoth = fn ($key) => match ($key) {
            'filter_category_id' => $catFabric->id,
            'filter_supplier_id' => $supA->id,
            default => null,
        };
        $results = MaterialFormFilterHelper::applyFilters(Material::query(), $getBoth)->get();
        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($mat1));
    }

    public function test_category_and_supplier_filter_components_instantiation(): void
    {
        $catSelect = MaterialFormFilterHelper::categoryFilter();
        $this->assertEquals('filter_category_id', $catSelect->getName());
        $this->assertEquals('Material Category', $catSelect->getLabel());

        $supSelect = MaterialFormFilterHelper::supplierFilter();
        $this->assertEquals('filter_supplier_id', $supSelect->getName());
        $this->assertEquals('Material Supplier', $supSelect->getLabel());
    }

    public function test_merchandise_planning_and_bom_item_accessors_expose_category_and_supplier(): void
    {
        $cat = MaterialCategory::create(['name' => 'Leather', 'code' => 'LTH', 'company_id' => $this->company->id]);
        $sup = Supplier::create(['name' => 'Supplier L', 'code' => 'SUP-L', 'company_id' => $this->company->id]);
        $mat = Material::create([
            'code' => 'MAT-L1',
            'name' => 'Italian Leather',
            'category' => 'leather',
            'category_id' => $cat->id,
            'supplier_id' => $sup->id,
            'company_id' => $this->company->id,
        ]);

        $bomItem = new BomItem(['material_id' => $mat->id]);
        $this->assertEquals($cat->id, $bomItem->filter_category_id);
        $this->assertEquals($sup->id, $bomItem->filter_supplier_id);

        $merchItem = new MerchandisePlanningItem(['material_id' => $mat->id, 'supplier_id' => $sup->id]);
        $this->assertEquals($cat->id, $merchItem->filter_category_id);
        $this->assertEquals($sup->id, $merchItem->supplier_id);
    }
}
