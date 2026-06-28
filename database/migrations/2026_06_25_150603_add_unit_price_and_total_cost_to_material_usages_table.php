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
        Schema::table('material_usages', function (Blueprint $table) {
            $table->decimal('unit_price', 15, 2)->default(0)->after('unit');
            $table->decimal('total_cost', 15, 2)->default(0)->after('unit_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_usages', function (Blueprint $table) {
            $table->dropColumn(['unit_price', 'total_cost']);
        });
    }
};
