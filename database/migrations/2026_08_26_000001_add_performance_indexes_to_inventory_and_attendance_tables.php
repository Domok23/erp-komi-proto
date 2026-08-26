<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_stocks', function (Blueprint $table) {
            $table->index(['company_id', 'material_id'], 'inventory_stocks_company_material_idx');
        });

        Schema::table('hr_attendances', function (Blueprint $table) {
            $table->index(['company_id', 'date'], 'hr_attendances_company_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_stocks', function (Blueprint $table) {
            $table->dropIndex('inventory_stocks_company_material_idx');
        });

        Schema::table('hr_attendances', function (Blueprint $table) {
            $table->dropIndex('hr_attendances_company_date_idx');
        });
    }
};
