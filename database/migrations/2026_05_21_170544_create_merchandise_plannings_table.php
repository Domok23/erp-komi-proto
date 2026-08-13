<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchandise_plannings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('set null');
            $table->foreignId('design_id')->nullable()->constrained('rd_designs')->onDelete('set null');
            $table->date('planning_date')->nullable();
            $table->enum('status', ['preliminary', 'tech_pack', 'finalised', 'cancelled'])->default('preliminary');
            $table->decimal('total_material_cost', 15, 2)->default(0);
            $table->decimal('total_subcon_cost', 15, 2)->default(0);
            $table->text('special_instructions')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'project_id']);
            $table->index(['company_id', 'design_id']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchandise_plannings');
    }
};
