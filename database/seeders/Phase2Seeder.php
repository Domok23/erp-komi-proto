<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\JobOrder;
use App\Models\JobOrderMaterial;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\MerchandisePlanning;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderMaterial;
use App\Models\Project;
use App\Models\QcInspection;
use App\Models\SalesOrder;
use App\Models\Shipment;
use App\Models\SubProject;
use App\Services\CodeGenerator;
use Illuminate\Database\Seeder;

class Phase2Seeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('code', 'KEI')->first();
        if (! $company) {
            $this->command->error('Company KEI not found. Please run DataSeeder first.');

            return;
        }

        $project = Project::where('type', 'mass')->first();
        if (! $project) {
            $this->command->error('Mass production project not found. Please run DataSeeder first.');

            return;
        }

        $subProject = SubProject::where('project_id', $project->id)->first();
        $merchandisingPlanning = MerchandisePlanning::where('project_id', $project->id)->first();

        // Seed Phase 2: Production Orders
        $productionOrder = ProductionOrder::create([
            'company_id' => $company->id,
            'production_number' => CodeGenerator::generateProductionOrderNumber(),
            'project_id' => $project->id,
            'sub_project_id' => $subProject?->id,
            'merchandising_planning_id' => $merchandisingPlanning ? $merchandisingPlanning->id : null,
            'planned_qty' => 1000,
            'completed_qty' => 0,
            'status' => 'planned',
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(45)->toDateString(),
            'notes' => 'Mass production for Nike order',
        ]);

        if ($merchandisingPlanning) {
            foreach ($merchandisingPlanning->items as $item) {
                if ($item->material_id) {
                    ProductionOrderMaterial::create([
                        'company_id' => $company->id,
                        'production_order_id' => $productionOrder->id,
                        'merchandising_planning_item_id' => $item->id,
                        'material_id' => $item->material_id,
                        'planned_qty' => $item->planned_qty,
                        'unit' => $item->unit,
                        'is_selected' => true,
                    ]);
                }
            }
        }

        $this->command->info('Production Order created: '.$productionOrder->production_number);

        // Seed Phase 2: Job Orders
        $jobOrderCutting = JobOrder::create([
            'company_id' => $company->id,
            'production_order_id' => $productionOrder->id,
            'merchandising_planning_id' => $merchandisingPlanning ? $merchandisingPlanning->id : null,
            'job_order_number' => CodeGenerator::generateJobOrderNumber(),
            'task_type' => 'cutting',
            'planned_qty' => 1000,
            'completed_qty' => 0,
            'status' => 'pending',
            'assigned_to' => 'Cutting Team A',
        ]);

        $jobOrderSewing = JobOrder::create([
            'company_id' => $company->id,
            'production_order_id' => $productionOrder->id,
            'merchandising_planning_id' => $merchandisingPlanning ? $merchandisingPlanning->id : null,
            'job_order_number' => CodeGenerator::generateJobOrderNumber(),
            'task_type' => 'sewing',
            'planned_qty' => 1000,
            'completed_qty' => 0,
            'status' => 'pending',
            'assigned_to' => 'Sewing Team B',
        ]);

        $jobOrderFinishing = JobOrder::create([
            'company_id' => $company->id,
            'production_order_id' => $productionOrder->id,
            'merchandising_planning_id' => $merchandisingPlanning ? $merchandisingPlanning->id : null,
            'job_order_number' => CodeGenerator::generateJobOrderNumber(),
            'task_type' => 'finishing',
            'planned_qty' => 1000,
            'completed_qty' => 0,
            'status' => 'pending',
            'assigned_to' => 'Finishing Team C',
        ]);

        if ($merchandisingPlanning) {
            foreach ($merchandisingPlanning->items as $item) {
                if ($item->material_id) {
                    JobOrderMaterial::create([
                        'company_id' => $company->id,
                        'job_order_id' => $jobOrderCutting->id,
                        'merchandising_planning_item_id' => $item->id,
                        'material_id' => $item->material_id,
                        'planned_qty' => $item->planned_qty,
                        'unit' => $item->unit,
                        'is_selected' => true,
                    ]);
                    JobOrderMaterial::create([
                        'company_id' => $company->id,
                        'job_order_id' => $jobOrderSewing->id,
                        'merchandising_planning_item_id' => $item->id,
                        'material_id' => $item->material_id,
                        'planned_qty' => $item->planned_qty,
                        'unit' => $item->unit,
                        'is_selected' => true,
                    ]);
                    JobOrderMaterial::create([
                        'company_id' => $company->id,
                        'job_order_id' => $jobOrderFinishing->id,
                        'merchandising_planning_item_id' => $item->id,
                        'material_id' => $item->material_id,
                        'planned_qty' => $item->planned_qty,
                        'unit' => $item->unit,
                        'is_selected' => true,
                    ]);
                }
            }
        }

        $this->command->info('Job Orders created: '.$jobOrderCutting->job_order_number.', '.$jobOrderSewing->job_order_number.', '.$jobOrderFinishing->job_order_number);

        // Seed Phase 2: Material Usages (Cutting & Sewing)
        $matFabric = Material::where('code', 'FAB-001')->first();
        if ($matFabric && $jobOrderCutting) {
            MaterialUsage::create([
                'company_id' => $company->id,
                'job_order_id' => $jobOrderCutting->id,
                'material_id' => $matFabric->id,
                'usage_date' => now()->addDays(10)->toDateString(),
                'planned_qty' => 1500.000,
                'actual_qty' => 1530.000,
                'waste_qty' => 30.000,
                'unit' => 'yard',
                'unit_price' => $matFabric->price ?? 38000,
                'total_cost' => 1530.000 * ($matFabric->price ?? 38000),
                'status' => 'planned',
                'notes' => 'Actual cutting fabric usage with 30 yards scrap/waste',
            ]);
        }

        $matZipper = Material::where('code', 'ZIP-001')->first();
        if ($matZipper && $jobOrderSewing) {
            MaterialUsage::create([
                'company_id' => $company->id,
                'job_order_id' => $jobOrderSewing->id,
                'material_id' => $matZipper->id,
                'usage_date' => now()->addDays(20)->toDateString(),
                'planned_qty' => 1000.000,
                'actual_qty' => 1010.000,
                'waste_qty' => 10.000,
                'unit' => 'pcs',
                'unit_price' => $matZipper->price ?? 7500,
                'total_cost' => 1010.000 * ($matZipper->price ?? 7500),
                'status' => 'planned',
                'notes' => 'Zipper assembly material usage report with 10 defective pcs',
            ]);
        }
        $this->command->info('Material Usages created.');

        // Seed Phase 2: QC Inspections
        $qcInspection = QcInspection::create([
            'company_id' => $company->id,
            'job_order_id' => $jobOrderFinishing->id,
            'inspection_number' => CodeGenerator::generateQcInspectionNumber(),
            'inspection_date' => now()->addDays(40)->toDateString(),
            'sample_size' => 50,
            'passed_qty' => 48,
            'failed_qty' => 2,
            'result' => 'pass',
            'inspector' => 'QC Supervisor',
            'notes' => 'Initial quality check passed with minor defects',
        ]);

        $this->command->info('QC Inspection created: '.$qcInspection->inspection_number);

        // Seed Phase 2: Outbound Sales Shipment
        $salesOrder = SalesOrder::where('company_id', $company->id)->first();
        if ($salesOrder) {
            $shipment = Shipment::create([
                'company_id' => $company->id,
                'shipment_number' => 'SHP-SALES-'.now()->year.'-001',
                'sales_order_id' => $salesOrder->id,
                'shipment_date' => now()->addDays(45)->toDateString(),
                'status' => 'pending',
                'shipping_method' => 'sea',
                'container_number' => 'MSKU-882194-0',
                'bl_number' => 'MAEU-982103948',
                'carrier' => 'Maersk Line',
                'port_of_loading' => 'Tanjung Priok, Jakarta',
                'port_of_discharge' => 'Port of Los Angeles, USA',
                'etd' => now()->addDays(46)->toDateString(),
                'eta' => now()->addDays(65)->toDateString(),
                'total_packages' => 50,
                'total_gross_weight_kg' => 850.50,
                'total_volume_m3' => 12.4000,
                'shipping_cost_usd' => 3500.00,
                'notes' => 'First export shipment batch for Nike mass production order',
            ]);

            $this->command->info('Sales Outbound Shipment created: '.$shipment->shipment_number);
        }

        $this->command->info('Phase 2 seeding completed successfully!');
    }
}
