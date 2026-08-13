<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('design_id')->constrained('rd_designs')->onDelete('cascade');
            $table->string('version')->default('1.0');
            $table->string('name');
            $table->enum('status', ['draft', 'active', 'discontinued'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'design_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boms');
    }
};
