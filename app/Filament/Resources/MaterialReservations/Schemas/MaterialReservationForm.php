<?php

namespace App\Filament\Resources\MaterialReservations\Schemas;

use App\Models\InventoryStock;
use App\Models\Material;
use App\Models\Project;
use App\Services\CodeGenerator;
use App\Services\CompanyContext;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class MaterialReservationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('document_number')
                    ->default(fn () => CodeGenerator::generateReservationNumber())
                    ->disabled()
                    ->dehydrated()
                    ->required()
                    ->maxLength(50),
                Forms\Components\Select::make('reservation_type')
                    ->options([
                        'project' => 'Project Specific',
                        'general' => 'General/Buffer',
                    ])
                    ->default('general')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state === 'general') {
                            $set('project_id', null);
                        }
                    }),
                Forms\Components\Select::make('project_id')
                    ->relationship('project', 'project_code')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->reactive()
                    ->visible(fn (callable $get) => $get('reservation_type') === 'project')
                    ->required(fn (callable $get) => $get('reservation_type') === 'project'),
                Forms\Components\Select::make('sub_project_id')
                    ->label('Sub-Project')
                    ->relationship('subProject', 'name', function ($query, Get $get) {
                        $projectId = $get('project_id');
                        if ($projectId) {
                            return $query->where('project_id', $projectId);
                        }

                        return $query;
                    })
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->reactive()
                    ->visible(function (Get $get) {
                        if ($get('reservation_type') !== 'project') {
                            return false;
                        }
                        $projectId = $get('project_id');
                        if (! $projectId) {
                            return false;
                        }
                        $project = Project::find($projectId);

                        return $project && $project->hasSubProjects();
                    })
                    ->required(function (Get $get) {
                        if ($get('reservation_type') !== 'project') {
                            return false;
                        }
                        $projectId = $get('project_id');
                        if (! $projectId) {
                            return false;
                        }
                        $project = Project::find($projectId);

                        return $project && $project->hasSubProjects();
                    }),
                Forms\Components\Select::make('warehouse_id')
                    ->relationship('warehouse', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive(),
                Forms\Components\Select::make('material_id')
                    ->relationship(
                        'material',
                        'name',
                        fn ($query, callable $get) => $query->whereHas('inventoryStocks', function ($q) use ($get) {
                            $companyId = CompanyContext::getCompanyId();
                            $warehouseId = $get('warehouse_id');
                            $q->where('company_id', $companyId);
                            if ($warehouseId) {
                                $q->where('warehouse_id', $warehouseId);
                            }
                            $q->where('available_qty', '>', 0);
                        })
                    )
                    ->getOptionLabelFromRecordUsing(function ($record, callable $get) {
                        $companyId = CompanyContext::getCompanyId();
                        $warehouseId = $get('warehouse_id');
                        $stock = InventoryStock::where('material_id', $record->id)
                            ->where('company_id', $companyId);
                        if ($warehouseId) {
                            $stock->where('warehouse_id', $warehouseId);
                        }
                        $stock = $stock->first();

                        $available = $stock ? $stock->available_qty : 0;

                        return "[{$record->code}] {$record->name} (Available: ".number_format($available, 3)." {$record->unit})";
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $material = $state ? Material::find($state, ['*']) : null;
                        if ($material) {
                            $set('unit', $material->unit);
                        } else {
                            $set('unit', null);
                        }
                    }),
                Forms\Components\TextInput::make('reserved_qty')
                    ->numeric()
                    ->default(0)
                    ->required()
                    ->minValue(0.001)
                    ->rules([
                        fn (Get $get) => function (string $attribute, $value, $fail) use ($get) {
                            $materialId = $get('material_id');
                            $warehouseId = $get('warehouse_id');
                            if (! $materialId || ! $warehouseId) {
                                return;
                            }
                            $companyId = CompanyContext::getCompanyId();
                            $stock = InventoryStock::where('material_id', $materialId)
                                ->where('company_id', $companyId)
                                ->where('warehouse_id', $warehouseId)
                                ->first();

                            $available = $stock ? $stock->available_qty : 0;
                            if (floatval($value) > $available) {
                                $fail("Reserved quantity cannot exceed available stock ({$available}).");
                            }
                        },
                    ]),
                Forms\Components\Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending' => 'Pending Approval',
                        'approved' => 'Approved',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('draft')
                    ->required(),
                Forms\Components\DatePicker::make('reservation_date')
                    ->default(now()->toDateString())
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }
}
