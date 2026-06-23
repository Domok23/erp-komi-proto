<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchandise_planning_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchandise_planning_id')
                ->constrained('merchandise_plannings')
                ->onDelete('cascade');
            $table->foreignId('material_id')->nullable()->constrained('materials')->onDelete('set null');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->foreignId('subcon_id')->nullable()->constrained('subcons')->onDelete('set null');
            $table->decimal('planned_qty', 15, 2)->default(0);
            $table->string('unit')->default('pcs');
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);
            $table->boolean('is_subcon')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchandise_planning_items');
    }
};
