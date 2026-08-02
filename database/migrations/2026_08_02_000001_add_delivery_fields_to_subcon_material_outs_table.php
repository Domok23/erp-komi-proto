<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subcon_material_outs', function (Blueprint $table) {
            $table->string('delivery_method')->nullable()->after('status');
            $table->string('courier_name')->nullable()->after('delivery_method');
            $table->decimal('delivery_cost', 12, 2)->nullable()->after('courier_name');
            $table->date('estimated_arrival')->nullable()->after('delivery_cost');
        });
    }

    public function down(): void
    {
        Schema::table('subcon_material_outs', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_method',
                'courier_name',
                'delivery_cost',
                'estimated_arrival',
            ]);
        });
    }
};
