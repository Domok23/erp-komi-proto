<?php

namespace Tests\Feature;

use App\Filament\Widgets\ProjectProgressBoard;
use App\Models\Company;
use App\Models\JobOrder;
use App\Models\MerchandisePlanning;
use App\Models\ProductionOrder;
use App\Models\Project;
use App\Models\User;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectProgressBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_widget_can_retrieve_project_progress_data(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $company = Company::create([
            'name' => 'PT Komitrando Test',
            'code' => 'KMT-TEST',
            'address' => 'Jogja',
        ]);

        // Put company into session for scope
        session(['selected_company_id' => $company->id]);

        $project = Project::create([
            'company_id' => $company->id,
            'project_code' => 'PRJ-TEST-001',
            'name' => 'Test Project',
            'type' => 'mass',
            'status' => 'production',
            'target_date' => now()->addDays(5),
        ]);

        $planning = MerchandisePlanning::create([
            'company_id' => $company->id,
            'project_id' => $project->id,
            'planning_date' => now()->toDateString(),
            'status' => 'finalised',
        ]);

        $prodOrder = ProductionOrder::create([
            'company_id' => $company->id,
            'production_number' => 'PO-TEST-001',
            'project_id' => $project->id,
            'merchandising_planning_id' => $planning->id,
            'status' => 'in_progress',
            'planned_qty' => 100,
        ]);

        $jobOrder = JobOrder::create([
            'company_id' => $company->id,
            'production_order_id' => $prodOrder->id,
            'merchandising_planning_id' => $planning->id,
            'job_order_number' => 'JO-TEST-001',
            'task_type' => 'sewing',
            'planned_qty' => 100,
            'status' => 'in_progress',
        ]);

        $widget = new ProjectProgressBoard;

        // Assert the query works
        $query = $widget->table(Table::make($widget))->getQuery();
        $this->assertNotNull($query);

        $results = $query->get();
        $this->assertCount(1, $results);
        $this->assertEquals('PRJ-TEST-001', $results->first()->project_code);
    }
}
