<?php

namespace App\Services;

use App\Models\Costing;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CostingTransitionService
{
    public static function submit(Costing $costing): Costing
    {
        if ($costing->selling_price <= 0) {
            throw ValidationException::withMessages([
                'status' => 'Cannot submit costing with Selling Price of 0. Please calculate costs first.',
            ]);
        }

        $costing->update([
            'status' => 'submitted',
            'submitted_by' => Auth::id(),
            'submitted_at' => now(),
        ]);

        return $costing;
    }

    public static function approve(Costing $costing): Costing
    {
        $costing->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return $costing;
    }

    public static function reject(Costing $costing): Costing
    {
        $costing->update([
            'status' => 'rejected',
            'rejected_by' => Auth::id(),
            'rejected_at' => now(),
        ]);

        return $costing;
    }

    public static function createNewVersion(Costing $costing): Costing
    {
        if ($costing->status !== 'rejected') {
            return $costing;
        }

        $newVersion = self::getNextVersion($costing->project_id);

        return Costing::create([
            'company_id'          => $costing->company_id,
            'project_id'          => $costing->project_id,
            'design_id'           => $costing->design_id,
            'costing_date'        => now()->toDateString(),
            'version'             => $newVersion,
            'status'              => 'draft',
            'material_cost'       => $costing->material_cost,
            'mp_cost'             => $costing->mp_cost,
            'overhead_pct'        => $costing->overhead_pct,
            'overhead_amount'     => $costing->overhead_amount,
            'shipping_cost'       => $costing->shipping_cost,
            'profit_margin_pct'   => $costing->profit_margin_pct,
            'profit_margin_amount'=> $costing->profit_margin_amount,
            'landed_cost'         => $costing->landed_cost,
            'selling_price'       => $costing->selling_price,
            'currency'            => $costing->currency,
            'notes'               => $costing->notes,
        ]);
    }

    public static function duplicate(Costing $costing): Costing
    {
        $newVersion = self::getNextVersion($costing->project_id);

        return Costing::create([
            'company_id'          => $costing->company_id,
            'project_id'          => $costing->project_id,
            'design_id'           => $costing->design_id,
            'costing_date'        => now()->toDateString(),
            'version'             => $newVersion,
            'status'              => 'draft',
            'material_cost'       => $costing->material_cost,
            'mp_cost'             => $costing->mp_cost,
            'overhead_pct'        => $costing->overhead_pct,
            'overhead_amount'     => $costing->overhead_amount,
            'shipping_cost'       => $costing->shipping_cost,
            'profit_margin_pct'   => $costing->profit_margin_pct,
            'profit_margin_amount'=> $costing->profit_margin_amount,
            'landed_cost'         => $costing->landed_cost,
            'selling_price'       => $costing->selling_price,
            'currency'            => $costing->currency,
            'notes'               => $costing->notes,
        ]);
    }

    /**
     * Get next version number for a project by finding the highest existing version.
     */
    public static function getNextVersion(int $projectId): string
    {
        $maxVersion = Costing::where('project_id', $projectId)
            ->get()
            ->map(fn (Costing $c) => $c->version)
            ->map(fn (string $v) => self::versionToNumber($v))
            ->max();

        if ($maxVersion === null) {
            return '1.0';
        }

        $parts = explode('.', (string) $maxVersion);
        $major = (int) ($parts[0] ?? 1);
        $minor = (int) ($parts[1] ?? 0) + 1;

        return $major . '.' . $minor;
    }

    private static function versionToNumber(string $version): float
    {
        if (preg_match('/^(\d+)\.(\d+)$/', $version, $matches)) {
            return (float) ($matches[1] . '.' . $matches[2]);
        }

        return (float) $version;
    }
}
