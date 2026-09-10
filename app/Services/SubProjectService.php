<?php

namespace App\Services;

use App\Models\Project;
use App\Models\SubProject;

class SubProjectService
{
    public static function copyForTransition(Project $from, Project $to): void
    {
        $query = $from->subProjects();

        foreach ($query->get() as $sp) {
            SubProject::create([
                'company_id' => $to->company_id,
                'project_id' => $to->id,
                'name' => $sp->name,
                'code' => $sp->code,
                'category' => $sp->category,
                'design_id' => $sp->design_id,
                'target_qty' => $sp->target_qty,
                'produced_qty' => 0,
            ]);
        }
    }

    public static function copyForDuplicate(Project $from, Project $to): void
    {
        self::copyForTransition($from, $to);
    }
}
