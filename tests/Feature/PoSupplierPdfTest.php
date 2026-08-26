<?php

namespace Tests\Feature;

use App\Filament\Resources\PoSupplierResource\Pages\ListPoSuppliers;
use App\Models\Company;
use App\Models\Material;
use App\Models\PoSupplier;
use App\Models\PoSupplierItem;
use App\Models\Project;
use App\Models\Supplier;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PoSupplierPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_download_po_supplier_pdf_action_executes_cleanly(): void
    {
        $company = Company::create([
            'name' => 'PT Komitrando Emporio Test',
            'code' => 'KOMI-TEST',
            'address' => 'Jogja',
        ]);

        CompanyContext::setCompany($company);

        $supplier = Supplier::create([
            'company_id' => $company->id,
            'name' => 'Supplier Textile Corp',
            'code' => 'SUP-TEX',
            'email' => 'john@textile.com',
            'phone' => '1111111',
            'address' => 'Bandung',
        ]);

        $project = Project::create([
            'company_id' => $company->id,
            'project_code' => 'PRJ-2026-001',
            'name' => 'Project Alpha',
            'type' => 'mass',
            'status' => 'planning',
        ]);

        $material = Material::create([
            'company_id' => $company->id,
            'code' => 'MAT-01',
            'name' => 'Fabric Black',
            'category' => 'fabric',
            'unit' => 'yard',
            'price' => 15000,
            'stock' => 0,
        ]);

        $po = PoSupplier::create([
            'company_id' => $company->id,
            'po_number' => 'PO-SUP-2026-001',
            'supplier_id' => $supplier->id,
            'project_id' => $project->id,
            'project_ids' => [$project->id],
            'po_date' => now()->toDateString(),
            'ppn_percent' => 11,
            'status' => 'draft',
            'subtotal' => 150000,
            'ppn_amount' => 16500,
            'grand_total' => 166500,
        ]);

        PoSupplierItem::create([
            'po_supplier_id' => $po->id,
            'project_id' => $project->id,
            'material_id' => $material->id,
            'description' => 'Fabric Black Raw Material',
            'qty' => 10,
            'unit' => 'yard',
            'unit_price' => 15000,
            'total_price' => 150000,
        ]);

        Livewire::test(ListPoSuppliers::class)
            ->callTableAction('downloadPdf', $po)
            ->assertSuccessful();
    }
}
