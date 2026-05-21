<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_boms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->decimal('quantity', 15, 3);
            $table->decimal('wastage_pct', 5, 2)->default(5);
            $table->decimal('final_quantity', 15, 3);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_boms');
    }
};
