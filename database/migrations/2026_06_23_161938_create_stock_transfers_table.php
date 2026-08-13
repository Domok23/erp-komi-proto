<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('to_company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->string('transfer_number', 50)->unique();
            $table->date('transfer_date');
            $table->enum('status', ['draft', 'pending', 'shipped', 'received', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['from_company_id', 'transfer_number']);
            $table->index(['to_company_id', 'transfer_number']);
            $table->index(['status', 'transfer_date']);
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('materials')->onDelete('cascade');
            $table->decimal('qty_requested', 15, 3)->default(0);
            $table->decimal('qty_transferred', 15, 3)->default(0);
            $table->string('unit')->default('pcs');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
    }
};
