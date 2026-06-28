<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_order_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('production_order_id')->constrained('production_orders')->onDelete('cascade');
            $table->unsignedBigInteger('merchandising_planning_item_id');
            $table->foreign('merchandising_planning_item_id', 'pom_mp_item_id_foreign')->references('id')->on('merchandise_planning_items')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->decimal('planned_qty', 10, 3)->default(0);
            $table->string('unit')->default('pcs');
            $table->boolean('is_selected')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['production_order_id', 'merchandising_planning_item_id'], 'pom_po_mp_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_order_materials');
    }
};
