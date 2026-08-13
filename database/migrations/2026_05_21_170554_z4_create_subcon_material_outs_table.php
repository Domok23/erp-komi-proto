<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subcon_material_outs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('po_subcon_id')->nullable()->constrained('po_subcons')->onDelete('set null');
            $table->foreignId('subcon_id')->constrained('subcons')->onDelete('cascade');
            $table->string('document_number')->unique();
            $table->date('departure_date');
            $table->string('status')->default('draft'); // draft, sent, received
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'document_number']);
            $table->index(['company_id', 'subcon_id']);
        });

        Schema::create('subcon_material_out_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subcon_material_out_id')->constrained('subcon_material_outs')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->decimal('qty_sent', 15, 2)->default(0);
            $table->string('unit')->default('pcs');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subcon_material_out_items');
        Schema::dropIfExists('subcon_material_outs');
    }
};
