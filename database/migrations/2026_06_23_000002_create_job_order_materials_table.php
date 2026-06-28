<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_order_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('job_order_id')->constrained('job_orders')->onDelete('cascade');
            $table->unsignedBigInteger('merchandising_planning_item_id');
            $table->foreign('merchandising_planning_item_id', 'jom_mp_item_id_foreign')->references('id')->on('merchandise_planning_items')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->decimal('planned_qty', 10, 3)->default(0);
            $table->decimal('usage_qty', 10, 3)->default(0);
            $table->decimal('leftover_qty', 10, 3)->default(0);
            $table->string('unit')->default('pcs');
            $table->boolean('is_selected')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['job_order_id', 'merchandising_planning_item_id'], 'jom_jo_mp_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_order_materials');
    }
};
