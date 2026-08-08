<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add project_ids (json) to po_suppliers and po_subcons
        Schema::table('po_suppliers', function (Blueprint $table) {
            $table->json('project_ids')->nullable()->after('po_number');
        });

        Schema::table('po_subcons', function (Blueprint $table) {
            $table->json('project_ids')->nullable()->after('po_number');
        });

        // 2. Add project_id to po_supplier_items and po_subcon_items
        Schema::table('po_supplier_items', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('po_supplier_id')->constrained('projects')->nullOnDelete();
        });

        Schema::table('po_subcon_items', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('po_subcon_id')->constrained('projects')->nullOnDelete();
        });

        // 3. Backfill data from existing records
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("
                UPDATE po_suppliers 
                SET project_ids = JSON_ARRAY(CAST(project_id AS CHAR)) 
                WHERE project_id IS NOT NULL AND (project_ids IS NULL OR project_ids = '[]')
            ");

            DB::statement("
                UPDATE po_subcons 
                SET project_ids = JSON_ARRAY(CAST(project_id AS CHAR)) 
                WHERE project_id IS NOT NULL AND (project_ids IS NULL OR project_ids = '[]')
            ");

            DB::statement("
                UPDATE po_supplier_items item
                LEFT JOIN sub_projects sp ON item.sub_project_id = sp.id
                LEFT JOIN po_suppliers po ON item.po_supplier_id = po.id
                SET item.project_id = COALESCE(sp.project_id, po.project_id)
            ");

            DB::statement("
                UPDATE po_subcon_items item
                LEFT JOIN sub_projects sp ON item.sub_project_id = sp.id
                LEFT JOIN po_subcons po ON item.po_subcon_id = po.id
                SET item.project_id = COALESCE(sp.project_id, po.project_id)
            ");
        } else {
            // Portable Eloquent/DB loop for SQLite / testing
            foreach (DB::table('po_suppliers')->whereNotNull('project_id')->get() as $po) {
                DB::table('po_suppliers')->where('id', $po->id)->update([
                    'project_ids' => json_encode([$po->project_id]),
                ]);
            }
            foreach (DB::table('po_subcons')->whereNotNull('project_id')->get() as $po) {
                DB::table('po_subcons')->where('id', $po->id)->update([
                    'project_ids' => json_encode([$po->project_id]),
                ]);
            }
            foreach (DB::table('po_supplier_items')->get() as $item) {
                $projId = null;
                if ($item->sub_project_id) {
                    $projId = DB::table('sub_projects')->where('id', $item->sub_project_id)->value('project_id');
                }
                if (!$projId && $item->po_supplier_id) {
                    $projId = DB::table('po_suppliers')->where('id', $item->po_supplier_id)->value('project_id');
                }
                if ($projId) {
                    DB::table('po_supplier_items')->where('id', $item->id)->update(['project_id' => $projId]);
                }
            }
            foreach (DB::table('po_subcon_items')->get() as $item) {
                $projId = null;
                if ($item->sub_project_id) {
                    $projId = DB::table('sub_projects')->where('id', $item->sub_project_id)->value('project_id');
                }
                if (!$projId && $item->po_subcon_id) {
                    $projId = DB::table('po_subcons')->where('id', $item->po_subcon_id)->value('project_id');
                }
                if ($projId) {
                    DB::table('po_subcon_items')->where('id', $item->id)->update(['project_id' => $projId]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('po_supplier_items', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });

        Schema::table('po_subcon_items', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });

        Schema::table('po_suppliers', function (Blueprint $table) {
            $table->dropColumn('project_ids');
        });

        Schema::table('po_subcons', function (Blueprint $table) {
            $table->dropColumn('project_ids');
        });
    }
};
