<?php

namespace Tests\Feature;

use App\Models\Bom;
use App\Models\Company;
use App\Models\RdDesign;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BomValidationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private RdDesign $design;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Test Company',
            'code' => 'TEST',
            'address' => 'Test Address',
        ]);

        $this->design = RdDesign::create([
            'company_id' => $this->company->id,
            'code' => 'DES-001',
            'name' => 'Test Design',
            'product_type' => 'jacket',
            'status' => 'draft',
        ]);
    }

    /**
     * Test that BOM number is generated automatically in the format:
     * BOM-YYYY-[design_id]-[version]
     */
    public function test_bom_number_is_generated_automatically_on_creation(): void
    {
        $bom = Bom::create([
            'company_id' => $this->company->id,
            'design_id' => $this->design->id,
            'version' => '1.0',
            'name' => 'Backpack BOM',
            'status' => 'draft',
        ]);

        $year = date('Y');
        $expectedNumber = "BOM-{$year}-{$this->design->id}-1.0";

        $this->assertNotNull($bom->bom_number);
        $this->assertEquals($expectedNumber, $bom->bom_number);
        $this->assertDatabaseHas('boms', [
            'id' => $bom->id,
            'bom_number' => $expectedNumber,
        ]);
    }

    /**
     * Test that creating duplicate version for the same design throws exception
     */
    public function test_cannot_create_duplicate_version_for_same_design(): void
    {
        Bom::create([
            'company_id' => $this->company->id,
            'design_id' => $this->design->id,
            'version' => '1.0',
            'name' => 'BOM v1.0',
            'status' => 'draft',
        ]);

        $this->expectException(QueryException::class);

        // Attempting to create another BOM for the same design and version
        Bom::create([
            'company_id' => $this->company->id,
            'design_id' => $this->design->id,
            'version' => '1.0',
            'name' => 'Duplicate BOM v1.0',
            'status' => 'draft',
        ]);
    }

    /**
     * Test that different versions for the same design can coexist
     */
    public function test_can_create_multiple_versions_for_same_design(): void
    {
        $bom1 = Bom::create([
            'company_id' => $this->company->id,
            'design_id' => $this->design->id,
            'version' => '1.0',
            'name' => 'BOM v1.0',
            'status' => 'draft',
        ]);

        $bom2 = Bom::create([
            'company_id' => $this->company->id,
            'design_id' => $this->design->id,
            'version' => '1.1',
            'name' => 'BOM v1.1',
            'status' => 'draft',
        ]);

        $year = date('Y');
        $this->assertEquals("BOM-{$year}-{$this->design->id}-1.0", $bom1->bom_number);
        $this->assertEquals("BOM-{$year}-{$this->design->id}-1.1", $bom2->bom_number);

        $this->assertDatabaseHas('boms', ['id' => $bom1->id]);
        $this->assertDatabaseHas('boms', ['id' => $bom2->id]);
    }

    /**
     * Test that updating version updates the BOM number accordingly
     */
    public function test_bom_number_is_updated_when_version_changes(): void
    {
        $bom = Bom::create([
            'company_id' => $this->company->id,
            'design_id' => $this->design->id,
            'version' => '1.0',
            'name' => 'Backpack BOM',
            'status' => 'draft',
        ]);

        $year = date('Y');
        $this->assertEquals("BOM-{$year}-{$this->design->id}-1.0", $bom->bom_number);

        $bom->update(['version' => '2.0']);

        $this->assertEquals("BOM-{$year}-{$this->design->id}-2.0", $bom->fresh()->bom_number);
    }
}
