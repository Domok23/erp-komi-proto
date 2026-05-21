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
        Schema::create('rd_designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('category', [
                'handbag',
                'backpack',
                'duffle',
                'messenger',
                'laptop_bag',
                'trolley',
                'other',
            ]);
            $table->enum('status', ['draft', 'approved', 'archived'])->default('draft');
            $table->string('sample_photo')->nullable();
            $table->string('tech_drawing')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('estimated_material_cost', 15, 2)->nullable();
            $table->decimal('estimated_mp_cost', 15, 2)->nullable();
            $table->decimal('estimated_overhead_pct', 5, 2)->default(15);
            $table->decimal('estimated_profit_margin_pct', 5, 2)->default(20);
            $table->decimal('estimated_selling_price', 15, 2)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'code']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rd_designs');
    }
};