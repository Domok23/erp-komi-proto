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
        Schema::create('merchandisings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('set null');
            $table->foreignId('design_id')->nullable()->constrained('rd_designs')->onDelete('set null');
            $table->string('version')->default('1.0');
            $table->enum('status', ['preliminary', 'tech_pack', 'finalised', 'cancelled'])->default('preliminary');
            $table->json('materials_spec')->nullable();
            $table->json('colors')->nullable();
            $table->json('measurements')->nullable();
            $table->text('special_instructions')->nullable();
            $table->date('issued_date')->nullable();
            $table->string('issued_by')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'project_id']);
            $table->index(['company_id', 'design_id']);
            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchandisings');
    }
};