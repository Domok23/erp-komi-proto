<?php

namespace App\Filament\Support;

use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\Supplier;
use App\Services\CompanyContext;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MaterialFormFilterHelper
{
    /**
     * Reusable Quick Category Filter Select Component.
     */
    public static function categoryFilter(
        string $name = 'filter_category_id',
        string $label = 'Material Category',
        ?string $materialFieldName = 'material_id',
        bool $dehydrated = false
    ): Select {
        return Select::make($name)
            ->label($label)
            ->placeholder('All Categories')
            ->options(function () {
                $companyId = CompanyContext::getCompanyId();

                return MaterialCategory::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->toArray();
            })
            ->searchable()
            ->preload()
            ->live()
            ->dehydrated($dehydrated)
            ->afterStateHydrated(function (Select $component, $state, callable $get, ?Model $record) use ($materialFieldName) {
                if (! $state && $materialFieldName) {
                    $materialId = $get($materialFieldName) ?? $record?->{$materialFieldName};
                    if ($materialId) {
                        $mat = Material::find($materialId);
                        if ($mat?->category_id) {
                            $component->state($mat->category_id);
                        }
                    }
                }
            })
            ->afterStateUpdated(function ($state, callable $set, callable $get) use ($materialFieldName) {
                if ($materialFieldName) {
                    $currentMaterialId = $get($materialFieldName);
                    if ($currentMaterialId) {
                        $mat = Material::find($currentMaterialId);
                        if ($mat && $state && (int) $mat->category_id !== (int) $state && $mat->category !== $state) {
                            $set($materialFieldName, null);
                        }
                    }
                }
            });
    }

    /**
     * Reusable Quick Supplier Filter Select Component (Cascaded by Category if selected).
     */
    public static function supplierFilter(
        string $name = 'filter_supplier_id',
        string $label = 'Material Supplier',
        string $categoryFieldName = 'filter_category_id',
        ?string $materialFieldName = 'material_id',
        bool $dehydrated = false
    ): Select {
        return Select::make($name)
            ->label($label)
            ->placeholder('All Suppliers')
            ->options(function (callable $get) use ($categoryFieldName) {
                $companyId = CompanyContext::getCompanyId();
                $categoryId = $get($categoryFieldName);

                return Supplier::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->when($categoryId, function ($q) use ($categoryId) {
                        $q->whereHas('materials', function ($m) use ($categoryId) {
                            $m->where(function ($sub) use ($categoryId) {
                                $sub->where('category_id', $categoryId)
                                    ->orWhere('category', $categoryId);
                            });
                        });
                    })
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->toArray();
            })
            ->searchable()
            ->preload()
            ->live()
            ->dehydrated($dehydrated)
            ->afterStateHydrated(function (Select $component, $state, callable $get, ?Model $record) use ($materialFieldName) {
                if (! $state && $materialFieldName) {
                    $materialId = $get($materialFieldName) ?? $record?->{$materialFieldName};
                    if ($materialId) {
                        $mat = Material::find($materialId);
                        if ($mat?->supplier_id) {
                            $component->state($mat->supplier_id);
                        }
                    }
                }
            })
            ->afterStateUpdated(function ($state, callable $set, callable $get) use ($materialFieldName) {
                if ($materialFieldName) {
                    $currentMaterialId = $get($materialFieldName);
                    if ($currentMaterialId) {
                        $mat = Material::find($currentMaterialId);
                        if ($mat && $state && (int) $mat->supplier_id !== (int) $state) {
                            $set($materialFieldName, null);
                        }
                    }
                }
            });
    }

    /**
     * Apply active Category and Supplier quick filters to any Material query.
     */
    public static function applyFilters(
        Builder $query,
        callable $get,
        string $categoryFieldName = 'filter_category_id',
        string $supplierFieldName = 'filter_supplier_id'
    ): Builder {
        $categoryId = $get($categoryFieldName);
        $supplierId = $get($supplierFieldName);

        return $query
            ->when($categoryId, function ($q, $catId) {
                $q->where(function ($sub) use ($catId) {
                    $sub->where('category_id', $catId)
                        ->orWhere('category', $catId);
                });
            })
            ->when($supplierId, function ($q, $supId) {
                $q->where('supplier_id', $supId);
            });
    }
}
