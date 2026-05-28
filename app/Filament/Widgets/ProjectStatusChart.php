<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use Filament\Widgets\ChartWidget;

class ProjectStatusChart extends ChartWidget
{
    protected static ?int $sort = 3;
    protected ?string $heading = 'Projects by Status';
    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $planning = Project::where('status', 'planning')->count();
        $development = Project::where('status', 'development')->count();
        $sampling = Project::where('status', 'sampling')->count();
        $production = Project::where('status', 'production')->count();
        $completed = Project::where('status', 'completed')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Projects',
                    'data' => [$planning, $development, $sampling, $production, $completed],
                    'backgroundColor' => [
                        '#94a3b8', // planning - gray
                        '#38bdf8', // development - blue
                        '#fbbf24', // sampling - amber
                        '#f59e0b', // production - orange
                        '#34d399', // completed - emerald
                    ],
                ],
            ],
            'labels' => ['Planning', 'Development', 'Sampling', 'Production', 'Completed'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
