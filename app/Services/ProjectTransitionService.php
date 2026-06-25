<?php

namespace App\Services;

use App\Models\Project;
use Carbon\Carbon;

class ProjectTransitionService
{
    public static function approveProject(Project $project, int $userId): Project
    {
        $project->update([
            'status' => 'approved',
            'approved_at' => Carbon::now(),
            'approved_by' => $userId,
        ]);

        if ($project->type === 'proto') {
            // Auto-create Sample project
            return Project::create([
                'company_id' => $project->company_id,
                'project_code' => CodeGenerator::generateProjectCode(),
                'name' => $project->name . ' - Sample',
                'description' => $project->description,
                'type' => 'sample',
                'status' => 'planning',
                'customer_id' => $project->customer_id,
                'design_id' => $project->design_id,
                'bom_id' => $project->bom_id,
                'reference_project_id' => $project->id,
                'target_qty' => 1,
            ]);
        } elseif ($project->type === 'sample') {
            // Auto-create Mass Production project
            $nameWithoutSample = str_replace(' - Sample', '', $project->name);
            return Project::create([
                'company_id' => $project->company_id,
                'project_code' => CodeGenerator::generateProjectCode(),
                'name' => $nameWithoutSample . ' - Mass',
                'description' => $project->description,
                'type' => 'mass',
                'status' => 'planning',
                'customer_id' => $project->customer_id,
                'design_id' => $project->design_id,
                'bom_id' => $project->bom_id,
                'reference_project_id' => $project->id,
                'target_qty' => $project->target_qty > 1 ? $project->target_qty : 100, // default dummy mass qty
            ]);
        }

        return $project;
    }

    public static function duplicateProject(Project $project): Project
    {
        return Project::create([
            'company_id' => $project->company_id,
            'project_code' => CodeGenerator::generateProjectCode(),
            'name' => $project->name . ' (Copy)',
            'description' => $project->description,
            'type' => $project->type,
            'status' => 'planning',
            'customer_id' => $project->customer_id,
            'design_id' => $project->design_id,
            'bom_id' => $project->bom_id,
            'reference_project_id' => $project->reference_project_id,
            'target_qty' => $project->target_qty,
        ]);
    }
}
