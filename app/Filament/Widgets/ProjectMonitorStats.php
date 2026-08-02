<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class ProjectMonitorStats extends BaseWidget
{
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $liveQuery = Project::query()
            ->whereIn('status', ['planning', 'development', 'sampling', 'production']);

        $totalLive = (clone $liveQuery)->count();

        $allLiveProjects = (clone $liveQuery)->get();
        $today = Carbon::now()->startOfDay();

        $onTrackCount = 0;
        $atRiskCount = 0;
        $overdueCount = 0;

        foreach ($allLiveProjects as $project) {
            $progress = $project->progressPercent();
            $targetDate = $project->target_date ? $project->target_date->startOfDay() : null;
            $isOverdue = $targetDate && $targetDate->isPast() && ! $targetDate->isToday();

            if ($isOverdue) {
                $overdueCount++;
            }

            if ($progress >= 50 && (! $targetDate || $targetDate->gte($today))) {
                $onTrackCount++;
            } elseif ($progress < 50 && $targetDate && ($targetDate->diffInDays($today, false) >= -7)) {
                $atRiskCount++;
            }
        }

        return [
            Stat::make('Total Live Projects', $totalLive)
                ->description('Active planning, sampling & production')
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('primary'),

            Stat::make('On Track', $onTrackCount)
                ->description('Progress ≥ 50% and on schedule')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('At Risk', $atRiskCount)
                ->description('Progress < 50% & due within 7 days')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),

            Stat::make('Overdue Projects', $overdueCount)
                ->description('Past target delivery date')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($overdueCount > 0 ? 'danger' : 'gray'),
        ];
    }
}
