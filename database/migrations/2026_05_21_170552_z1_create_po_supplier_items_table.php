<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_supplier_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('po_supplier_id')->constrained('po_suppliers')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->string('description')->nullable();
            $table->decimal('qty', 15, 2)->default(0);
            $table->string('unit')->default('pcs');
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);
            $table->decimal('qty_received', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_supplier_items');
    }
};
