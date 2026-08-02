<?php

namespace Tests\Feature;

use App\Exceptions\SubProjectException;
use App\Models\Company;
use App\Models\Costing;
use App\Models\Project;
use App\Models\SubProject;
use App\Support\SubProjectTransactionRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubProjectTransactionRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_flat_project_allows_null_sub_project(): void
    {
        $company = Company::create(['name' => 'C', 'code' => 'C1', 'address' => 'X']);
        $project = Project::create([
            'company_id' => $company->id,
            'project_code' => 'P1',
            'name' => 'Flat Project',
            'type' => 'mass',
            'status' => 'planning',
            'target_qty' => 10,
            'produced_qty' => 0,
        ]);

        SubProjectTransactionRules::assertValid($project->id, null);
        $this->assertTrue(true);
    }

    public function test_project_with_sub_project_requires_sub_project_id(): void
    {
        $company = Company::create(['name' => 'C', 'code' => 'C2', 'address' => 'X']);
        $project = Project::create([
            'company_id' => $company->id,
            'project_code' => 'P2',
            'name' => 'SubProject Project',
            'type' => 'mass',
            'status' => 'planning',
            'target_qty' => 10,
            'produced_qty' => 0,
        ]);

        $sp = SubProject::create([
            'company_id' => $company->id,
            'project_id' => $project->id,
            'name' => 'Varian Hitam',
            'review_status' => 'approved',
            'target_qty' => 5,
            'produced_qty' => 0,
        ]);

        $this->expectException(SubProjectException::class);
        SubProjectTransactionRules::assertValid($project->id, null);
    }

    public function test_sub_project_id_matching_project_passes(): void
    {
        $company = Company::create(['name' => 'C', 'code' => 'C3', 'address' => 'X']);
        $project = Project::create([
            'company_id' => $company->id,
            'project_code' => 'P3',
            'name' => 'SubProject Project',
            'type' => 'mass',
            'status' => 'planning',
            'target_qty' => 10,
            'produced_qty' => 0,
        ]);

        $sp = SubProject::create([
            'company_id' => $company->id,
            'project_id' => $project->id,
            'name' => 'Varian Hitam',
            'review_status' => 'approved',
            'target_qty' => 5,
            'produced_qty' => 0,
        ]);

        SubProjectTransactionRules::assertValid($project->id, $sp->id);
        $this->assertTrue(true);
    }

    public function test_sub_project_id_mismatch_throws_exception(): void
    {
        $company = Company::create(['name' => 'C', 'code' => 'C4', 'address' => 'X']);
        $project1 = Project::create([
            'company_id' => $company->id,
            'project_code' => 'P4',
            'name' => 'Project 1',
            'type' => 'mass',
            'status' => 'planning',
            'target_qty' => 10,
            'produced_qty' => 0,
        ]);
        $project2 = Project::create([
            'company_id' => $company->id,
            'project_code' => 'P5',
            'name' => 'Project 2',
            'type' => 'mass',
            'status' => 'planning',
            'target_qty' => 10,
            'produced_qty' => 0,
        ]);

        $spOfProject2 = SubProject::create([
            'company_id' => $company->id,
            'project_id' => $project2->id,
            'name' => 'Varian Merah',
            'review_status' => 'approved',
            'target_qty' => 5,
            'produced_qty' => 0,
        ]);

        $this->expectException(SubProjectException::class);
        SubProjectTransactionRules::assertValid($project1->id, $spOfProject2->id);
    }
}
