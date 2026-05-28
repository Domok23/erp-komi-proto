<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumption_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('design_id')->constrained('rd_designs')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->decimal('standard_rate', 15, 4);
            $table->string('unit')->default('pcs');
            $table->decimal('wastage_rate', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'design_id', 'material_id'], 'company_design_material_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumption_rates');
    }
};
