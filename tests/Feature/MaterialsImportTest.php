<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Material;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialsImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_decimal_sanitization(): void
    {
        $resourceClass = \App\Filament\Resources\MaterialResource::class;

        $this->assertEquals(100.5, $resourceClass::normalizeDecimal('100.5'));
        $this->assertEquals(100.5, $resourceClass::normalizeDecimal('100,5'));
        $this->assertEquals(1500.5, $resourceClass::normalizeDecimal('1.500,50'));
        $this->assertEquals(1500.5, $resourceClass::normalizeDecimal('1,500.50'));
        $this->assertEquals(1500.0, $resourceClass::normalizeDecimal('1,500'));
        $this->assertNull($resourceClass::normalizeDecimal(''));
    }

    public function test_category_normalization_and_mapping(): void
    {
        $resourceClass = \App\Filament\Resources\MaterialResource::class;

        // Exact matches
        $this->assertEquals('fabric', $resourceClass::normalizeCategory('fabric'));
        $this->assertEquals('zipper', $resourceClass::normalizeCategory('Zipper '));
        
        // Mapped values
        $this->assertEquals('finished', $resourceClass::normalizeCategory('Finished Product'));
        $this->assertEquals('finished', $resourceClass::normalizeCategory('finished_product'));
        $this->assertEquals('semi_finished', $resourceClass::normalizeCategory('Semi-Finished Product'));
        $this->assertEquals('semi_finished', $resourceClass::normalizeCategory('semi finished'));

        // Invalid values
        $this->assertNull($resourceClass::normalizeCategory(''));
        $this->assertNull($resourceClass::normalizeCategory('invalid_category_name'));
    }

    public function test_supplier_resolution_and_material_upsert(): void
    {
        $company = Company::create([
            'name' => 'Komi Company',
            'code' => 'KOMI',
            'address' => 'Komi Addr',
        ]);

        // Seed a supplier
        $supplier = Supplier::create([
            'company_id' => $company->id,
            'code' => 'SUP-TEST',
            'name' => 'Supplier Test Inc',
            'contact_person' => 'John',
            'is_active' => true,
        ]);

        $resourceClass = \App\Filament\Resources\MaterialResource::class;

        // 1. Test insertion of new material
        $code = 'MAT-NEW-99';
        $name = 'New Canvas Blue';
        $categoryRaw = 'Fabric';
        $unit = 'yard';
        $stockRaw = '1.200,50';
        $minStockRaw = '50';
        $priceRaw = '4.50';
        $supplierRaw = 'Supplier Test Inc';
        $description = 'New canvas fabric';

        $category = $resourceClass::normalizeCategory($categoryRaw);
        $stock = $resourceClass::normalizeDecimal($stockRaw);
        $minStock = $resourceClass::normalizeDecimal($minStockRaw);
        $price = $resourceClass::normalizeDecimal($priceRaw);

        $this->assertEquals('fabric', $category);
        $this->assertEquals(1200.5, $stock);
        $this->assertEquals(50.0, $minStock);
        $this->assertEquals(4.5, $price);

        // Find Supplier
        $resolvedSupplier = Supplier::where('name', $supplierRaw)
            ->orWhere('code', $supplierRaw)
            ->first();
        $this->assertNotNull($resolvedSupplier);
        $this->assertEquals($supplier->id, $resolvedSupplier->id);

        // Upsert new material
        $material = Material::where('code', $code)->first();
        $this->assertNull($material);

        $material = new Material();
        $material->code = $code;
        $material->name = $name;
        $material->category = $category;
        $material->unit = $unit;
        $material->stock = $stock;
        $material->min_stock = $minStock;
        $material->price = $price;
        $material->supplier_id = $resolvedSupplier->id;
        $material->description = $description;
        $material->is_active = true;
        $material->save();

        $inserted = Material::where('code', $code)->first();
        $this->assertNotNull($inserted);
        $this->assertEquals($name, $inserted->name);
        $this->assertEquals(1200.5, $inserted->stock);
        $this->assertEquals($supplier->id, $inserted->supplier_id);

        // 2. Test updating of existing material
        $updatedName = 'Updated Canvas Blue';
        $updatedStockRaw = '1,500';

        $updatedStock = $resourceClass::normalizeDecimal($updatedStockRaw);

        $materialToUpdate = Material::where('code', $code)->first();
        $this->assertNotNull($materialToUpdate);

        $materialToUpdate->name = $updatedName;
        $materialToUpdate->stock = $updatedStock;
        $materialToUpdate->save();

        $updated = Material::where('code', $code)->first();
        $this->assertEquals($updatedName, $updated->name);
        $this->assertEquals(1500.0, $updated->stock);
    }
}
