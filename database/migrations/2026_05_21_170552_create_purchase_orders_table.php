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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('po_number')->unique();
            $table->enum('type', ['supplier', 'subcon', 'shipping'])->default('supplier');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers');
            $table->foreignId('subcon_id')->nullable()->constrained('subcons');
            $table->date('order_date');
            $table->date('delivery_date')->nullable();
            $table->enum('status', ['draft', 'sent', 'partial', 'received', 'cancelled'])->default('draft');
            $table->string('currency')->default('USD');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};