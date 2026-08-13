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
        Schema::create('material_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('set null');
            $table->string('document_number')->unique();
            $table->enum('reservation_type', ['project', 'general'])->default('general');
            $table->decimal('reserved_qty', 15, 3)->default(0);
            $table->enum('status', ['draft', 'pending', 'approved', 'cancelled'])->default('draft');
            $table->date('reservation_date')->default(now());
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'warehouse_id', 'material_id']);
            $table->index(['company_id', 'project_id']);
            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_reservations');
    }
};
