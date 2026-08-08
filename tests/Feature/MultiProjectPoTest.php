<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Material;
use App\Models\PoSupplier;
use App\Models\Project;
use App\Models\SubProject;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiProjectPoTest extends TestCase
{
    use RefreshDatabase;

    public function test_multi_project_po_creation_and_item_allocation(): void
    {
        $company = Company::create(['name' => 'PT Komitrando', 'code' => 'KOM-01']);
        $supplier = Supplier::create([
            'company_id' => $company->id,
            'name' => 'PT Supplier Utama',
            'code' => 'SUP-001',
        ]);
        $material = Material::create([
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'name' => 'Kain Cotton',
            'code' => 'MAT-COT-01',
            'category' => 'fabric',
            'unit' => 'm',
            'price' => 5000,
        ]);

        // Project A with sub-projects
        $projA = Project::create([
            'company_id' => $company->id,
            'project_code' => 'PRJ-ALPHA',
            'name' => 'Project Alpha',
            'status' => 'development',
        ]);
        $subA1 = SubProject::create([
            'company_id' => $company->id,
            'project_id' => $projA->id,
            'name' => 'Sub Alpha 1',
            'code' => 'SA1',
        ]);

        // Project B flat without sub-projects
        $projB = Project::create([
            'company_id' => $company->id,
            'project_code' => 'PRJ-BETA',
            'name' => 'Project Beta',
            'status' => 'development',
        ]);

        $po = PoSupplier::create([
            'company_id' => $company->id,
            'po_number' => 'PO-MULTI-001',
            'supplier_id' => $supplier->id,
            'project_ids' => [$projA->id, $projB->id],
            'po_date' => now(),
            'status' => 'draft',
        ]);

        // Item 1 allocated to SubProject of Project A
        $po->items()->create([
            'project_id' => $projA->id,
            'sub_project_id' => $subA1->id,
            'material_id' => $material->id,
            'description' => 'Item for Sub Alpha 1',
            'qty' => 10,
            'unit_price' => 5000,
            'total_price' => 50000,
        ]);

        // Item 2 allocated directly to flat Project B
        $po->items()->create([
            'project_id' => $projB->id,
            'sub_project_id' => null,
            'material_id' => $material->id,
            'description' => 'Item for Project Beta',
            'qty' => 20,
            'unit_price' => 5000,
            'total_price' => 100000,
        ]);

        $this->assertCount(2, $po->items);
        $this->assertEquals('Project Alpha, Project Beta', $po->project_names);

        // Verify PDF renders cleanly
        $pdf = Pdf::loadView('pdf.po-supplier', [
            'po' => $po->fresh(['items.subProject', 'items.material']),
            'company' => $company,
            'supplier' => $supplier,
        ]);

        $this->assertNotEmpty($pdf->output());
    }
}
