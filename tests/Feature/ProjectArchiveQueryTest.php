<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectArchiveQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_chart_counts_exclude_archived(): void
    {
        $company = Company::create([
            'name' => 'PT Chart Test',
            'code' => 'CHART-'.uniqid(),
            'address' => 'Jogja',
        ]);

        Project::create([
            'company_id' => $company->id,
            'project_code' => 'PRJ-C1',
            'name' => 'Active Completed',
            'type' => 'proto',
            'status' => 'completed',
            'target_qty' => 1,
            'produced_qty' => 0,
        ]);

        Project::create([
            'company_id' => $company->id,
            'project_code' => 'PRJ-C2',
            'name' => 'Archived Completed',
            'type' => 'proto',
            'status' => 'completed',
            'target_qty' => 1,
            'produced_qty' => 0,
            'archived_at' => now(),
        ]);

        $completedActive = Project::where('company_id', $company->id)->active()->where('status', 'completed')->count();
        $this->assertSame(1, $completedActive);
    }

    public function test_active_picker_query_excludes_archived(): void
    {
        $company = Company::create([
            'name' => 'PT Picker Test',
            'code' => 'PICK-'.uniqid(),
            'address' => 'Jogja',
        ]);

        $active = Project::create([
            'company_id' => $company->id,
            'project_code' => 'PRJ-P1',
            'name' => 'Active',
            'type' => 'proto',
            'status' => 'planning',
            'target_qty' => 1,
            'produced_qty' => 0,
        ]);

        $archived = Project::create([
            'company_id' => $company->id,
            'project_code' => 'PRJ-P2',
            'name' => 'Archived',
            'type' => 'proto',
            'status' => 'completed',
            'target_qty' => 1,
            'produced_qty' => 0,
            'archived_at' => now(),
        ]);

        $ids = Project::where('company_id', $company->id)->active()->pluck('id')->all();
        $this->assertContains($active->id, $ids);
        $this->assertNotContains($archived->id, $ids);
    }
}
