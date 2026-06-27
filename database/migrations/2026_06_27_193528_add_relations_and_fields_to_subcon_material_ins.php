<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subcon_material_ins', function (Blueprint $table) {
            $table->foreignId('subcon_material_out_id')
                ->nullable()
                ->after('po_subcon_id')
                ->constrained('subcon_material_outs')
                ->onDelete('set null');
        });

        Schema::table('subcon_material_in_items', function (Blueprint $table) {
            $table->string('item_type')
                ->default('processed')
                ->after('material_id'); // processed, raw_return
        });
    }

    public function down(): void
    {
        Schema::table('subcon_material_in_items', function (Blueprint $table) {
            $table->dropColumn('item_type');
        });

        Schema::table('subcon_material_ins', function (Blueprint $table) {
            $table->dropForeign(['subcon_material_out_id']);
            $table->dropColumn('subcon_material_out_id');
        });
    }
};
