<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_projects', function (Blueprint $table) {
            $table->foreignId('design_id')
                ->nullable()
                ->after('category')
                ->constrained('rd_designs')
                ->nullOnDelete();
        });

        // Backfill design_id from existing bom_id if available
        if (Schema::hasTable('boms') && Schema::hasColumn('boms', 'design_id')) {
            DB::table('sub_projects')
                ->whereNotNull('bom_id')
                ->whereNull('design_id')
                ->get()
                ->each(function ($subProject) {
                    $designId = DB::table('boms')->where('id', $subProject->bom_id)->value('design_id');
                    if ($designId) {
                        DB::table('sub_projects')->where('id', $subProject->id)->update(['design_id' => $designId]);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::table('sub_projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('design_id');
        });
    }
};
