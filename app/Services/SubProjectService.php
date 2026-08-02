<?php

namespace App\Services;

use App\Models\Project;
use App\Models\SubProject;
use Carbon\Carbon;

class SubProjectService
{
    public static function setReviewStatus(
        SubProject $subProject,
        string $status,
        int $userId,
        ?string $notes = null
    ): SubProject {
        if (! in_array($status, ['pending', 'approved', 'rejected'], true)) {
            throw new \InvalidArgumentException("Invalid review status: {$status}");
        }

        $subProject->update([
            'review_status' => $status,
            'review_notes' => $notes,
            'reviewed_at' => Carbon::now(),
            'reviewed_by' => $userId,
        ]);

        if ($status === 'approved' && $subProject->project?->type === 'sample') {
            $parentProject = $subProject->project;
            $massProject = $parentProject->lifecycleChildren()->where('type', 'mass')->first();

            if ($massProject) {
                $alreadyInMass = $massProject->subProjects()
                    ->where('name', $subProject->name)
                    ->exists();

                if (! $alreadyInMass) {
                    SubProject::create([
                        'company_id' => $massProject->company_id,
                        'project_id' => $massProject->id,
                        'name' => $subProject->name,
                        'code' => $subProject->code,
                        'category' => $subProject->category,
                        'bom_id' => $subProject->bom_id,
                        'target_qty' => $subProject->target_qty,
                        'produced_qty' => 0,
                        'review_status' => 'approved',
                        'review_notes' => $subProject->review_notes,
                        'reviewed_at' => $subProject->reviewed_at ?? Carbon::now(),
                        'reviewed_by' => $subProject->reviewed_by ?? $userId,
                    ]);
                }
            }
        }

        return $subProject->fresh();
    }

    public static function copyForTransition(Project $from, Project $to, bool $onlyApproved): void
    {
        $query = $from->subProjects();
        if ($onlyApproved) {
            $query->where('review_status', 'approved');
        }

        $isMass = ($to->type === 'mass');

        foreach ($query->get() as $sp) {
            SubProject::create([
                'company_id' => $to->company_id,
                'project_id' => $to->id,
                'name' => $sp->name,
                'code' => $sp->code,
                'category' => $sp->category,
                'bom_id' => $sp->bom_id,
                'target_qty' => $sp->target_qty,
                'produced_qty' => 0,
                'review_status' => $isMass ? 'approved' : 'pending',
                'review_notes' => $isMass ? $sp->review_notes : null,
                'reviewed_at' => $isMass ? ($sp->reviewed_at ?? Carbon::now()) : null,
                'reviewed_by' => $isMass ? $sp->reviewed_by : null,
            ]);
        }
    }

    public static function copyForDuplicate(Project $from, Project $to): void
    {
        self::copyForTransition($from, $to, onlyApproved: false);
    }
}
