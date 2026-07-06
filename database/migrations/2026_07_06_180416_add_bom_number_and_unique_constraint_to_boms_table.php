<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('boms', function (Blueprint $table) {
            $table->string('bom_number')->nullable()->after('company_id');
        });

        // Populate existing BOM records using the format BOM-YYYY-[design_id]-[version]
        $boms = DB::table('boms')->orderBy('id')->get();
        foreach ($boms as $bom) {
            $year = date('Y', strtotime($bom->created_at ?: now()));
            $number = "BOM-{$year}-{$bom->design_id}-{$bom->version}";
            DB::table('boms')->where('id', $bom->id)->update(['bom_number' => $number]);
        }

        Schema::table('boms', function (Blueprint $table) {
            $table->string('bom_number')->nullable(false)->change();
            $table->unique('bom_number');
            $table->unique(['design_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boms', function (Blueprint $table) {
            $table->dropUnique(['boms_design_id_version_unique']);
            $table->dropUnique(['boms_bom_number_unique']);
            $table->dropColumn('bom_number');
        });
    }
};
