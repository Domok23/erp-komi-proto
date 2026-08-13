<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Change enum category -> varchar (preserve old data)
        DB::statement('ALTER TABLE materials MODIFY COLUMN category VARCHAR(100) NULL');

        Schema::table('materials', function (Blueprint $table) {
            $table->renameColumn('unit', 'uom');
            $table->string('size')->nullable()->after('name');
            $table->string('color')->nullable()->after('size');
            $table->boolean('is_import')->default(false)->after('price');
            $table->foreignId('category_id')->nullable()->after('category')
                ->constrained('material_categories')->onDelete('set null');
            $table->foreignId('uom_id')->nullable()->after('uom')
                ->constrained('material_uoms')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropConstrainedForeignId('uom_id');
            $table->dropColumn(['size', 'color', 'is_import']);
            $table->renameColumn('uom', 'unit');
        });

        DB::statement("ALTER TABLE materials MODIFY COLUMN category ENUM('raw_material','components','consumables','fabric','zipper','button','thread','handle','label','interlining','semi_finished','finished','other') NULL");
    }
};
