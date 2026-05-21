<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->date('consumption_date');
            $table->decimal('quantity', 15, 3);
            $table->enum('source', ['from_warehouse', 'from_subcon', 'adjustment'])->default('from_warehouse');
            $table->text('notes')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_consumptions');
    }
};
