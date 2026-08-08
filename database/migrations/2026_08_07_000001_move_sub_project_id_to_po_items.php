<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add sub_project_id to item tables
        Schema::table('po_supplier_items', function (Blueprint $table) {
            $table->foreignId('sub_project_id')
                ->nullable()
                ->constrained('sub_projects')
                ->restrictOnDelete();
        });

        Schema::table('po_subcon_items', function (Blueprint $table) {
            $table->foreignId('sub_project_id')
                ->nullable()
                ->constrained('sub_projects')
                ->restrictOnDelete();
        });

        // 2. Copy sub_project_id from header to items
        DB::statement('
            UPDATE po_supplier_items
            SET sub_project_id = (
                SELECT sub_project_id FROM po_suppliers
                WHERE po_suppliers.id = po_supplier_items.po_supplier_id
            )
        ');

        DB::statement('
            UPDATE po_subcon_items
            SET sub_project_id = (
                SELECT sub_project_id FROM po_subcons
                WHERE po_subcons.id = po_subcon_items.po_subcon_id
            )
        ');

        // 3. Drop sub_project_id from header tables
        Schema::table('po_suppliers', function (Blueprint $table) {
            $table->dropForeign(['sub_project_id']);
            $table->dropColumn('sub_project_id');
        });

        Schema::table('po_subcons', function (Blueprint $table) {
            $table->dropForeign(['sub_project_id']);
            $table->dropColumn('sub_project_id');
        });
    }

    public function down(): void
    {
        // Re-add sub_project_id to headers
        Schema::table('po_suppliers', function (Blueprint $table) {
            $table->foreignId('sub_project_id')
                ->nullable()
                ->constrained('sub_projects')
                ->restrictOnDelete();
        });

        Schema::table('po_subcons', function (Blueprint $table) {
            $table->foreignId('sub_project_id')
                ->nullable()
                ->constrained('sub_projects')
                ->restrictOnDelete();
        });

        // Copy back from first item's sub_project_id to header
        DB::statement('
            UPDATE po_suppliers
            SET sub_project_id = (
                SELECT sub_project_id FROM po_supplier_items
                WHERE po_supplier_items.po_supplier_id = po_suppliers.id
                LIMIT 1
            )
        ');

        DB::statement('
            UPDATE po_subcons
            SET sub_project_id = (
                SELECT sub_project_id FROM po_subcon_items
                WHERE po_subcon_items.po_subcon_id = po_subcons.id
                LIMIT 1
            )
        ');

        // Drop from item tables
        Schema::table('po_supplier_items', function (Blueprint $table) {
            $table->dropForeign(['sub_project_id']);
            $table->dropColumn('sub_project_id');
        });

        Schema::table('po_subcon_items', function (Blueprint $table) {
            $table->dropForeign(['sub_project_id']);
            $table->dropColumn('sub_project_id');
        });
    }
};
