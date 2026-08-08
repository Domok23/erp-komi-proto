<?php

namespace Tests\Feature;

use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Company;
use App\Models\ConsumptionRate;
use App\Models\Customer;
use App\Models\Material;
use App\Models\MerchandisePlanning;
use App\Models\MerchandisePlanningItem;
use App\Models\PoSubcon;
use App\Models\PoSubconItem;
use App\Models\PoSupplier;
use App\Models\PoSupplierItem;
use App\Models\Project;
use App\Models\RdDesign;
use App\Models\Subcon;
use App\Models\Supplier;
use App\Services\CodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComponentPipelineIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_field_propagates_across_full_pipeline(): void
    {
        // 1. Setup Company and Entities
        $company = Company::create([
            'name' => 'PT Komitrando Test',
            'code' => 'KOMI-TEST',
            'address' => 'Yogyakarta',
        ]);

        $customer = Customer::create([
            'company_id' => $company->id,
            'code' => 'CUST-001',
            'name' => 'Customer A',
            'email' => 'customer.a@example.com',
            'phone' => '1234567890',
            'address' => 'Singapore',
        ]);

        $supplier = Supplier::create([
            'company_id' => $company->id,
            'name' => 'Supplier Textile Corp',
            'code' => 'SUP-TEX',
            'contact_person' => 'John Textile',
            'email' => 'john@textile.com',
            'phone' => '1111111',
            'address' => 'Bandung',
        ]);

        $subcon = Subcon::create([
            'company_id' => $company->id,
            'name' => 'Sewing Subcon Jogja',
            'code' => 'SUB-SEW',
            'service_type' => 'sewing',
            'contact_person' => 'Slamet Sewing',
            'email' => 'slamet@sewing.com',
            'phone' => '2222222',
            'address' => 'Sleman',
        ]);

        $material = Material::create([
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'code' => 'MAT-FABRIC-01',
            'name' => 'Cordura Fabric',
            'category' => 'fabric',
            'unit' => 'yard',
            'price' => 50000,
            'stock' => 0,
        ]);

        $subconMaterial = Material::create([
            'company_id' => $company->id,
            'code' => 'MAT-EMBROIDERY-01',
            'name' => 'Embroidery Service',
            'category' => 'service',
            'unit' => 'pcs',
            'price' => 15000,
            'stock' => 0,
        ]);

        // 2. R&D: Create Design & Consumption Rates with Component
        $design = RdDesign::create([
            'company_id' => $company->id,
            'code' => 'DSN-BAG-01',
            'name' => 'Backpack Alpha',
            'product_type' => 'other',
            'status' => 'approved',
        ]);

        $cr1 = ConsumptionRate::create([
            'company_id' => $company->id,
            'design_id' => $design->id,
            'material_id' => $material->id,
            'component' => 'Front Body',
            'standard_rate' => 1.25,
            'unit' => 'yard',
            'wastage_rate' => 5,
        ]);

        $cr2 = ConsumptionRate::create([
            'company_id' => $company->id,
            'design_id' => $design->id,
            'material_id' => $subconMaterial->id,
            'component' => 'Logo Patch',
            'standard_rate' => 1.0,
            'unit' => 'pcs',
            'wastage_rate' => 0,
        ]);

        $this->assertEquals('Front Body', $cr1->component);
        $this->assertEquals('Logo Patch', $cr2->component);

        // 3. BOM Auto-Population from Consumption Rates
        $rates = ConsumptionRate::where('design_id', $design->id)->get();
        $bomItemsData = $rates->map(function ($rate) {
            return [
                'material_id' => $rate->material_id,
                'component' => $rate->component,
                'category' => 'main_material',
                'quantity_per_unit' => $rate->standard_rate,
                'unit' => $rate->unit,
                'wastage_percent' => $rate->wastage_rate,
                'notes' => $rate->notes,
                'is_from_rnd' => true,
            ];
        })->toArray();

        $bom = Bom::create([
            'company_id' => $company->id,
            'design_id' => $design->id,
            'code' => 'BOM-001',
            'name' => 'BOM Backpack Alpha',
            'status' => 'approved',
        ]);

        foreach ($bomItemsData as $itemData) {
            $bom->items()->create($itemData);
        }

        $this->assertCount(2, $bom->items);
        $this->assertEquals('Front Body', $bom->items()->where('material_id', $material->id)->first()->component);
        $this->assertEquals('Logo Patch', $bom->items()->where('material_id', $subconMaterial->id)->first()->component);

        // 4. Project & Merchandise Planning Auto-Population
        $project = Project::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'design_id' => $design->id,
            'bom_id' => $bom->id,
            'project_code' => 'PRJ-2026-001',
            'name' => 'Project Alpha Batch 1',
            'target_qty' => 100,
            'status' => 'planning',
        ]);

        $planningItemsData = $project->bom->items->map(function ($bomItem) use ($project) {
            $unitPrice = $bomItem->material?->price ?? 0;
            $targetQty = max(1, (int) ($project->target_qty ?? 1));
            $wastageMultiplier = 1 + (($bomItem->wastage_percent ?? 0) / 100);
            $plannedQty = floatval($bomItem->quantity_per_unit) * $targetQty * $wastageMultiplier;

            return [
                'material_id' => $bomItem->material_id,
                'component' => $bomItem->component,
                'supplier_id' => $bomItem->material?->supplier_id,
                'planned_qty' => $plannedQty,
                'unit' => $bomItem->unit,
                'unit_price' => $unitPrice,
                'total_price' => $plannedQty * $unitPrice,
                'is_subcon' => $bomItem->component === 'Logo Patch',
                'notes' => 'From BOM',
                'is_from_rnd' => $bomItem->is_from_rnd ?? true,
            ];
        })->toArray();

        $planning = MerchandisePlanning::create([
            'company_id' => $company->id,
            'project_id' => $project->id,
            'design_id' => $design->id,
            'planning_date' => now()->toDateString(),
            'status' => 'finalised',
            'total_material_cost' => 6562500,
            'total_subcon_cost' => 1500000,
        ]);

        foreach ($planningItemsData as $pItemData) {
            if ($pItemData['is_subcon']) {
                $pItemData['subcon_id'] = $subcon->id;
            }
            $planning->items()->create($pItemData);
        }

        $this->assertCount(2, $planning->items);
        $matPlanItem = $planning->items()->where('material_id', $material->id)->first();
        $subconPlanItem = $planning->items()->where('material_id', $subconMaterial->id)->first();
        $this->assertEquals('Front Body', $matPlanItem->component);
        $this->assertEquals('Logo Patch', $subconPlanItem->component);

        // 5. Generate POs from Merchandise Planning (Simulate generatePO Action)
        // Supplier PO
        $supplierItems = $planning->items->where('is_subcon', false)->groupBy('supplier_id');
        foreach ($supplierItems as $supplierId => $items) {
            $poSupplier = PoSupplier::create([
                'company_id' => $planning->company_id,
                'po_number' => CodeGenerator::generatePOSupplierNo(),
                'project_id' => $planning->project_id,
                'supplier_id' => $supplierId,
                'po_date' => now()->toDateString(),
                'ppn_percent' => 11,
                'status' => 'draft',
            ]);

            foreach ($items as $item) {
                PoSupplierItem::create([
                    'po_supplier_id' => $poSupplier->id,
                    'material_id' => $item->material_id,
                    'component' => $item->component,
                    'sub_project_id' => $planning->sub_project_id,
                    'description' => $item->notes ?? 'Raw material',
                    'qty' => $item->planned_qty,
                    'unit' => $item->unit ?? 'pcs',
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                    'qty_received' => 0,
                ]);
            }
        }

        // Subcon PO
        $subconItems = $planning->items->where('is_subcon', true)->groupBy('subcon_id');
        foreach ($subconItems as $subconId => $items) {
            $poSubcon = PoSubcon::create([
                'company_id' => $planning->company_id,
                'po_number' => CodeGenerator::generatePOSubconNo(),
                'project_id' => $planning->project_id,
                'subcon_id' => $subconId,
                'po_date' => now()->toDateString(),
                'status' => 'draft',
            ]);

            foreach ($items as $item) {
                PoSubconItem::create([
                    'po_subcon_id' => $poSubcon->id,
                    'project_id' => $planning->project_id,
                    'sub_project_id' => $planning->sub_project_id,
                    'component' => $item->component,
                    'description' => $item->notes ?? 'Subcon service',
                    'qty' => $item->planned_qty,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                ]);
            }
        }

        // 6. Assert PO Supplier Item has Component
        $savedPoSupplierItem = PoSupplierItem::where('material_id', $material->id)->first();
        $this->assertNotNull($savedPoSupplierItem);
        $this->assertEquals('Front Body', $savedPoSupplierItem->component);

        // 7. Assert PO Subcon Item has Component
        $savedPoSubconItem = PoSubconItem::first();
        $this->assertNotNull($savedPoSubconItem);
        $this->assertEquals('Logo Patch', $savedPoSubconItem->component);
    }
}
