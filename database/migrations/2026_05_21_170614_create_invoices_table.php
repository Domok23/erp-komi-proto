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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('invoice_number')->unique();
            $table->enum('type', ['sales', 'purchase'])->default('sales');
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders');
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders');
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers');
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->string('currency')->default('USD');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_pct', 5, 2)->default(11);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_tax_invoice')->default(false);
            $table->string('tax_invoice_number')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};