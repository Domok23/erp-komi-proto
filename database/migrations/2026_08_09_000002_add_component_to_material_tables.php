<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consumption_rates', function (Blueprint $table) {
            $table->string('component', 100)->nullable()->after('wastage_rate');
        });

        Schema::table('bom_items', function (Blueprint $table) {
            $table->string('component', 100)->nullable()->after('wastage_percent');
        });

        Schema::table('merchandise_planning_items', function (Blueprint $table) {
            $table->string('component', 100)->nullable()->after('notes');
        });

        Schema::table('po_supplier_items', function (Blueprint $table) {
            $table->string('component', 100)->nullable()->after('qty_received');
        });

        Schema::table('po_subcon_items', function (Blueprint $table) {
            $table->string('component', 100)->nullable()->after('total_price');
        });
    }

    public function down(): void
    {
        Schema::table('consumption_rates', function (Blueprint $table) {
            $table->dropColumn('component');
        });

        Schema::table('bom_items', function (Blueprint $table) {
            $table->dropColumn('component');
        });

        Schema::table('merchandise_planning_items', function (Blueprint $table) {
            $table->dropColumn('component');
        });

        Schema::table('po_supplier_items', function (Blueprint $table) {
            $table->dropColumn('component');
        });

        Schema::table('po_subcon_items', function (Blueprint $table) {
            $table->dropColumn('component');
        });
    }
};
