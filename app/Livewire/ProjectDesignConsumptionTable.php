<?php

namespace App\Livewire;

use App\Filament\Resources\MaterialResource;
use App\Models\ConsumptionRate;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class ProjectDesignConsumptionTable extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    #[Reactive]
    public ?int $designId = null;

    public function mount(?int $designId = null): void
    {
        $this->designId = $designId;
    }

    public function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->heading('R&D Material Consumption Formula (Per Unit)')
            ->description('Engineering Material Formula (EBOM) reference from the approved Tech Pack.')
            ->emptyStateHeading('No R&D Design Selected')
            ->emptyStateDescription('Select an Approved R&D Design above to load its material formula specifications.')
            ->emptyStateIcon('heroicon-o-document-chart-bar')
            ->query(
                ConsumptionRate::query()
                    ->with(['material.categoryRef'])
                    ->when($this->designId, fn ($q) => $q->where('design_id', $this->designId), fn ($q) => $q->whereRaw('1 = 0'))
            )
            ->columns([
                TextColumn::make('row_index')
                    ->label('#')
                    ->rowIndex(),
                TextColumn::make('component')
                    ->label('Component')
                    ->weight('bold')
                    ->sortable()
                    ->default('-'),
                TextColumn::make('material.name')
                    ->label('Material Name')
                    ->sortable()
                    ->searchable()
                    ->html()
                    ->formatStateUsing(function ($state, ConsumptionRate $record) {
                        if (! $state || ! $record->material_id) {
                            return $state ?? 'N/A';
                        }
                        $url = MaterialResource::getUrl('edit', ['record' => $record->material_id]);
                        $tooltip = $record->material?->code ? 'Code: '.$record->material->code : '';

                        return '<a href="'.$url.'" title="'.e($tooltip).'" class="hover:underline text-primary-600 dark:text-primary-400 font-medium cursor-pointer" onclick="event.stopPropagation()">'.e($state).'</a>';
                    })
                    ->tooltip(fn (ConsumptionRate $record) => $record->material?->code ? 'Code: '.$record->material->code : null)
                    ->default('N/A'),
                TextColumn::make('material.categoryRef.name')
                    ->label('Category')
                    ->badge()
                    ->sortable()
                    ->default(fn (ConsumptionRate $record) => $record->material?->category ?? '-'),
                TextColumn::make('standard_rate')
                    ->label('Actual Cons.')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('unit')
                    ->label('UOM')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->default(fn (ConsumptionRate $record) => $record->material?->uom ?? 'pcs'),
                TextColumn::make('wastage_rate')
                    ->label('Waste %')
                    ->formatStateUsing(fn ($state) => number_format((float) ($state ?? config('costing.wastage_pct', 3)), 2).'%')
                    ->sortable(),
                TextColumn::make('gross_rate')
                    ->label(new HtmlString('Gross Rate <span title="Total required consumption rate per unit including waste tolerance percentage" style="cursor: help; color: #888; font-weight: normal; margin-left: 2px;">ⓘ</span>'))
                    ->state(function (ConsumptionRate $record) {
                        $std = (float) $record->standard_rate;
                        $waste = (float) ($record->wastage_rate ?? config('costing.wastage_pct', 3));

                        return $std * (1 + ($waste / 100));
                    })
                    ->numeric(decimalPlaces: 2)
                    ->weight('bold')
                    ->color('warning'),
            ])
            ->paginated(false);
    }

    public function render()
    {
        return view('livewire.project-design-consumption-table');
    }
}
