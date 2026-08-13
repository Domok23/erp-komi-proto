<?php

namespace App\Support;

use App\Exceptions\SubProjectException;
use App\Models\Project;
use App\Models\SubProject;

class SubProjectTransactionRules
{
    public static function assertValid(?int $projectId, ?int $subProjectId): void
    {
        if (! $projectId) {
            return;
        }

        $project = Project::find($projectId);
        if (! $project) {
            return;
        }

        if ($project->hasSubProjects()) {
            if (! $subProjectId) {
                throw SubProjectException::subProjectRequired($project);
            }

            $subProject = SubProject::find($subProjectId);
            if (! $subProject || (int) $subProject->project_id !== (int) $projectId) {
                throw SubProjectException::subProjectMismatch();
            }
        } elseif ($subProjectId) {
            $subProject = SubProject::find($subProjectId);
            if (! $subProject || (int) $subProject->project_id !== (int) $projectId) {
                throw SubProjectException::subProjectMismatch();
            }
        }
    }
}
