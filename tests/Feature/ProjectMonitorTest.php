<?php

namespace Tests\Feature;

use App\Filament\Pages\ProjectMonitor;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Company;
use App\Models\ConsumptionRate;
use App\Models\InventoryStock;
use App\Models\Material;
use App\Models\Project;
use App\Models\RdDesign;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ProjectMaterialReadiness;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectMonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_can_be_accessed_by_authenticated_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $company = Company::create([
            'name' => 'PT Komitrando Monitor Test',
            'code' => 'KMT-MON',
            'address' => 'Jogja',
        ]);
        session(['selected_company_id' => $company->id]);

        $project = Project::create([
            'company_id' => $company->id,
            'project_code' => 'PRJ-MON-001',
            'name' => 'Monitor Live Project',
            'type' => 'mass',
            'status' => 'production',
            'target_qty' => 100,
            'produced_qty' => 60,
            'target_date' => now()->addDays(5),
        ]);

        $page = new ProjectMonitor;
        $query = $page->table(Table::make($page))->getQuery();

        $this->assertNotNull($query);
        $results = $query->get();

        $this->assertTrue($results->contains('id', $project->id));
        $this->assertEquals(60.0, $project->progressPercent());
        $this->assertEquals(5, $project->daysRemaining());
    }

    public function test_query_excludes_completed_and_cancelled_projects(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $company = Company::create([
            'name' => 'PT Test Scoping',
            'code' => 'KMT-SCP',
            'address' => 'Jogja',
        ]);
        session(['selected_company_id' => $company->id]);

        $liveProject = Project::create([
            'company_id' => $company->id,
            'project_code' => 'PRJ-LIVE',
            'name' => 'Live Project',
            'type' => 'mass',
            'status' => 'development',
        ]);

        $completedProject = Project::create([
            'company_id' => $company->id,
            'project_code' => 'PRJ-DONE',
            'name' => 'Completed Project',
            'type' => 'mass',
            'status' => 'completed',
        ]);

        $page = new ProjectMonitor;
        $results = $page->table(Table::make($page))->getQuery()->get();

        $this->assertTrue($results->contains('id', $liveProject->id));
        $this->assertFalse($results->contains('id', $completedProject->id));
    }

    public function test_material_readiness_service_calculates_correct_status(): void
    {
        $company = Company::create([
            'name' => 'PT Test Material Readiness',
            'code' => 'KMT-MAT',
            'address' => 'Jogja',
        ]);

        $material = Material::create([
            'company_id' => $company->id,
            'code' => 'MAT-FABRIC-01',
            'material_code' => 'MAT-FABRIC-01',
            'name' => 'Cotton Fabric',
            'category' => 'fabric',
            'unit' => 'm',
            'total_stock' => 500,
        ]);

        $design = RdDesign::create([
            'company_id' => $company->id,
            'code' => 'DSG-001',
            'name' => 'Test Design',
            'product_type' => 'shirt',
            'status' => 'approved',
        ]);

        $bom = Bom::create([
            'company_id' => $company->id,
            'design_id' => $design->id,
            'bom_code' => 'BOM-001',
            'name' => 'Main BOM',
            'version' => 1,
        ]);

        BomItem::create([
            'bom_id' => $bom->id,
            'material_id' => $material->id,
            'category' => 'main_material',
            'quantity_per_unit' => 2.0,
            'unit' => 'm',
            'wastage_percent' => 5.0,
        ]);

        $project = Project::create([
            'company_id' => $company->id,
            'project_code' => 'PRJ-MAT-01',
            'name' => 'Material Readiness Project',
            'target_qty' => 100,
            'bom_id' => $bom->id,
            'status' => 'production',
        ]);

        $warehouse = Warehouse::create([
            'company_id' => $company->id,
            'code' => 'WH-MAIN',
            'name' => 'Main Warehouse',
        ]);

        InventoryStock::create([
            'company_id' => $company->id,
            'warehouse_id' => $warehouse->id,
            'material_id' => $material->id,
            'available_qty' => 300,
            'quantity' => 300,
            'unit' => 'm',
        ]);

        $service = new ProjectMaterialReadiness;
        $status = $service->getProjectStatus($project);

        $this->assertEquals('Ready', $status);
    }

    public function test_material_readiness_using_rnd_consumption_rates(): void
    {
        $company = Company::create([
            'name' => 'PT Komitrando Cons Test',
            'code' => 'KMT-CONS',
            'address' => 'Jogja',
        ]);
        session(['selected_company_id' => $company->id]);

        $material = Material::create([
            'company_id' => $company->id,
            'code' => 'MAT-ZIPPER-01',
            'material_code' => 'MAT-ZIPPER-01',
            'name' => 'Nylon Zipper',
            'category' => 'accessories',
            'unit' => 'pcs',
            'total_stock' => 1000,
        ]);

        $design = RdDesign::create([
            'company_id' => $company->id,
            'code' => 'DSG-CONS-01',
            'name' => 'Zipper Bag Design',
            'product_type' => 'backpack',
            'status' => 'approved',
        ]);

        ConsumptionRate::create([
            'company_id' => $company->id,
            'design_id' => $design->id,
            'material_id' => $material->id,
            'category' => 'accessories',
            'standard_rate' => 2.0,
            'unit' => 'pcs',
            'wastage_rate' => 0.0,
        ]);

        $project = Project::create([
            'company_id' => $company->id,
            'project_code' => 'PRJ-CONS-01',
            'name' => 'R&D Readiness Project',
            'target_qty' => 50,
            'design_id' => $design->id,
            'status' => 'production',
        ]);

        $warehouse = Warehouse::create([
            'company_id' => $company->id,
            'code' => 'WH-CONS',
            'name' => 'Accessory Warehouse',
        ]);

        InventoryStock::create([
            'company_id' => $company->id,
            'warehouse_id' => $warehouse->id,
            'material_id' => $material->id,
            'available_qty' => 100, // 50 * 2 = 100 needed, exactly 100 available -> Ready
            'quantity' => 100,
            'unit' => 'pcs',
        ]);

        $service = new ProjectMaterialReadiness;
        $status = $service->getProjectStatus($project);
        $details = $service->getDetails($project);

        $this->assertEquals('Ready', $status);
        $this->assertCount(1, $details['items']);
        $this->assertEquals('Nylon Zipper', $details['items'][0]['material_name']);
        $this->assertEquals(100.0, $details['items'][0]['qty_needed']);
        $this->assertEquals(100.0, $details['items'][0]['qty_available']);
        $this->assertEquals('Ready', $details['items'][0]['status']);
    }
}
