<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $globalWastage = config('costing.wastage_pct', 3);
        DB::table('consumption_rates')->update(['wastage_rate' => $globalWastage]);
        DB::table('bom_items')->update(['wastage_percent' => $globalWastage]);
    }

    public function down(): void
    {
        // No-op rollback for data normalization
    }
};
