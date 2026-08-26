<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->index('name');
            $table->index('is_active');
            $table->index(['is_active', 'name']);
            $table->index(['is_active', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'category_id']);
            $table->dropIndex(['is_active', 'name']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['name']);
        });
    }
};
