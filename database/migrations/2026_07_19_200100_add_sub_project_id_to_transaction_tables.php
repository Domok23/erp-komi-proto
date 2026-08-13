<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'costings',
            'production_orders',
            'merchandise_plannings',
            'material_reservations',
            'po_suppliers',
            'po_subcons',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $tableBlueprint) {
                $tableBlueprint->foreignId('sub_project_id')
                    ->nullable()
                    ->constrained('sub_projects')
                    ->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'costings',
            'production_orders',
            'merchandise_plannings',
            'material_reservations',
            'po_suppliers',
            'po_subcons',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $tableBlueprint) {
                $tableBlueprint->dropForeign(['sub_project_id']);
                $tableBlueprint->dropColumn('sub_project_id');
            });
        }
    }
};
