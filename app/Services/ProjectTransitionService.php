<?php

namespace App\Services;

use App\Exceptions\SubProjectException;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProjectTransitionService
{
    public static function approveProject(Project $project, int $userId): Project
    {
        if ($project->type === 'sample' && $project->hasPendingSubProjectReviews()) {
            throw SubProjectException::pendingReviews($project);
        }

        return DB::transaction(function () use ($project, $userId) {
            $project->update([
                'status' => 'approved',
                'approved_at' => Carbon::now(),
                'approved_by' => $userId,
            ]);

            $next = null;

            if ($project->type === 'proto') {
                // Auto-create Sample project
                $next = Project::create([
                    'company_id' => $project->company_id,
                    'project_code' => CodeGenerator::generateProjectCode(),
                    'name' => $project->name.' - Sample',
                    'description' => $project->description,
                    'type' => 'sample',
                    'status' => 'planning',
                    'customer_id' => $project->customer_id,
                    'design_id' => $project->design_id,
                    'bom_id' => $project->bom_id,
                    'reference_project_id' => $project->id,
                    'target_qty' => 1,
                ]);

                SubProjectService::copyForTransition($project, $next, onlyApproved: false);
            } elseif ($project->type === 'sample') {
                // Auto-create Mass Production project
                $nameWithoutSample = str_replace(' - Sample', '', $project->name);

                $next = Project::create([
                    'company_id' => $project->company_id,
                    'project_code' => CodeGenerator::generateProjectCode(),
                    'name' => $nameWithoutSample.' - Mass',
                    'description' => $project->description,
                    'type' => 'mass',
                    'status' => 'planning',
                    'customer_id' => $project->customer_id,
                    'design_id' => $project->design_id,
                    'bom_id' => $project->bom_id,
                    'reference_project_id' => $project->id,
                    'target_qty' => $project->target_qty > 1 ? $project->target_qty : 100,
                ]);

                SubProjectService::copyForTransition($project, $next, onlyApproved: true);
            }

            return $next ?? $project;
        });
    }

    public static function duplicateProject(Project $project): Project
    {
        return DB::transaction(function () use ($project) {
            $copy = Project::create([
                'company_id' => $project->company_id,
                'project_code' => CodeGenerator::generateProjectCode(),
                'name' => $project->name.' (Copy)',
                'description' => $project->description,
                'type' => $project->type,
                'status' => 'planning',
                'customer_id' => $project->customer_id,
                'design_id' => $project->design_id,
                'bom_id' => $project->bom_id,
                'reference_project_id' => $project->reference_project_id,
                'target_qty' => $project->target_qty,
            ]);

            SubProjectService::copyForDuplicate($project, $copy);

            return $copy;
        });
    }
}
