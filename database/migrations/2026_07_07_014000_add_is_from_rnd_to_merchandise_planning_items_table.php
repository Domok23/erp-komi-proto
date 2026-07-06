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
        Schema::table('merchandise_planning_items', function (Blueprint $table) {
            $table->boolean('is_from_rnd')->nullable()->default(null)->after('is_subcon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchandise_planning_items', function (Blueprint $table) {
            $table->dropColumn('is_from_rnd');
        });
    }
};
