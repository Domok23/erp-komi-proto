<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\JobOrder;
use App\Models\JobOrderMaterial;
use App\Models\MerchandisePlanning;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderMaterial;
use App\Models\Project;
use App\Models\QcInspection;
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

        $merchandisingPlanning = MerchandisePlanning::where('project_id', $project->id)->first();

        // Seed Phase 2: Production Orders
        $productionOrder = ProductionOrder::create([
            'company_id' => $company->id,
            'production_number' => CodeGenerator::generateProductionOrderNumber(),
            'project_id' => $project->id,
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

        $this->command->info('Phase 2 seeding completed successfully!');
    }
}
