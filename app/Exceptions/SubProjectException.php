<?php

namespace App\Exceptions;

use App\Models\Project;
use RuntimeException;

class SubProjectException extends RuntimeException
{
    public static function pendingReviews(Project $project): self
    {
        return new self(
            "Cannot approve project {$project->project_code} to mass while sub-project reviews are still pending."
        );
    }

    public static function subProjectRequired(Project $project): self
    {
        return new self(
            "Project {$project->project_code} has sub-projects; sub_project_id is required."
        );
    }

    public static function subProjectMismatch(): self
    {
        return new self('sub_project_id does not belong to the selected project.');
    }
}
