<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('inventory_id')->constrained('inventories')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->enum('type', ['purchase', 'production_in', 'production_out', 'adjustment', 'shipment', 'return_in', 'return_out', 'transfer_in', 'transfer_out']);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('quantity', 15, 3);
            $table->decimal('before_qty', 15, 3);
            $table->decimal('after_qty', 15, 3);
            $table->text('notes')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
