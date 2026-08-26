<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rd_designs', function (Blueprint $table) {
            if (! Schema::hasColumn('rd_designs', 'version')) {
                $table->string('version', 20)->default('1.0')->after('name');
            }
            if (! Schema::hasColumn('rd_designs', 'parent_design_id')) {
                $table->foreignId('parent_design_id')
                    ->nullable()
                    ->after('version')
                    ->constrained('rd_designs')
                    ->nullOnDelete();
            }
            $table->string('product_type', 50)->default('backpack')->change();
        });
    }

    public function down(): void
    {
        Schema::table('rd_designs', function (Blueprint $table) {
            if (Schema::hasColumn('rd_designs', 'parent_design_id')) {
                $table->dropForeign(['parent_design_id']);
                $table->dropColumn('parent_design_id');
            }
            if (Schema::hasColumn('rd_designs', 'version')) {
                $table->dropColumn('version');
            }
        });
    }
};
