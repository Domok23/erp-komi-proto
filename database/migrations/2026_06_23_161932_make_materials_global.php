<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['company_id']);

            // Drop indexes
            $table->dropIndex(['company_id', 'code']);
            $table->dropIndex(['company_id', 'category']);

            // Drop column
            $table->dropColumn('company_id');

            // Add index on category (since it was previously compound with company_id)
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->index(['company_id', 'code']);
            $table->index(['company_id', 'category']);
            $table->dropIndex(['category']);
        });
    }
};
