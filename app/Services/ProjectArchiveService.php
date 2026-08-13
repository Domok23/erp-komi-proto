<?php

namespace App\Services;

use App\Exceptions\ProjectArchiveException;
use App\Models\Costing;
use App\Models\MerchandisePlanning;
use App\Models\ProductionOrder;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;

class ProjectArchiveService
{
    /** @return list<array{type: string, id: int, label: string}> */
    public static function blockers(Project $project): array
    {
        $blockers = [];

        $openPos = ProductionOrder::query()
            ->where('project_id', $project->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->get(['id', 'production_number', 'status']);

        foreach ($openPos as $po) {
            $blockers[] = [
                'type' => 'production_order',
                'id' => $po->id,
                'label' => "PO {$po->production_number} ({$po->status})",
            ];
        }

        $openPlans = MerchandisePlanning::query()
            ->where('project_id', $project->id)
            ->whereNotIn('status', ['finalised', 'cancelled'])
            ->get(['id', 'status']);

        foreach ($openPlans as $plan) {
            $blockers[] = [
                'type' => 'merchandise_planning',
                'id' => $plan->id,
                'label' => "Merchandise planning #{$plan->id} ({$plan->status})",
            ];
        }

        $openCostings = Costing::query()
            ->where('project_id', $project->id)
            ->whereNotIn('status', ['approved', 'rejected'])
            ->get(['id', 'version', 'status']);

        foreach ($openCostings as $costing) {
            $blockers[] = [
                'type' => 'costing',
                'id' => $costing->id,
                'label' => "Costing v{$costing->version} ({$costing->status})",
            ];
        }

        return $blockers;
    }

    public static function archive(Project $project, User $user, bool $force = false): void
    {
        if ($project->isArchived()) {
            return;
        }

        if ($force) {
            if (! $user->isAdmin()) {
                throw new ProjectArchiveException('Only admin users can force-archive projects.');
            }
        } else {
            if (! in_array($project->status, ['completed', 'cancelled'], true)) {
                throw new ProjectArchiveException(
                    'Only completed or cancelled projects can be archived. Contact admin to force-archive.'
                );
            }

            $blockers = self::blockers($project);
            if ($blockers !== []) {
                throw new ProjectArchiveException(
                    'Project has active transactions. Complete them first or ask admin to force-archive.',
                    $blockers,
                );
            }
        }

        $project->update([
            'archived_at' => Carbon::now(),
            'archived_by' => $user->id,
        ]);
    }

    public static function restore(Project $project, User $user): void
    {
        if (! $user->isAdmin()) {
            throw new ProjectArchiveException('Only admin users can restore projects.');
        }

        if (! $project->isArchived()) {
            throw new ProjectArchiveException('Project is not in archived status.');
        }

        $project->update([
            'archived_at' => null,
            'archived_by' => null,
        ]);
    }
}
