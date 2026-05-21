<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->enum('warehouse_type', ['main', 'branch', 'subcon'])->default('main');
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->decimal('quantity', 15, 3)->default(0);
            $table->decimal('reserved_qty', 15, 3)->default(0);
            $table->decimal('available_qty', 15, 3)->default(0);
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
