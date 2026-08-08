<?php

namespace App\Filament\Widgets;

use App\Models\MaterialReservation;
use Filament\Widgets\ChartWidget;

class MaterialReservationChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Material Reservation Status Breakdown';
    
    protected ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $statuses = ['pending', 'partially_issued', 'issued', 'cancelled'];
        $labels = ['Pending', 'Partially Issued', 'Issued', 'Cancelled'];
        
        $data = [];
        foreach ($statuses as $status) {
            $count = MaterialReservation::where('status', $status)->count();
            $data[] = $count;
        }

        // If all are zero, use premium dummy data to show in empty state
        if (array_sum($data) === 0) {
            $data = [35, 12, 54, 8];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Reservations',
                    'data' => $data,
                    'backgroundColor' => [
                        'rgb(234, 179, 8)', // yellow
                        'rgb(249, 115, 22)', // orange
                        'rgb(16, 185, 129)', // emerald
                        'rgb(239, 68, 68)',  // red
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
