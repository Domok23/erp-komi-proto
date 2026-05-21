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
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('so_number')->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->date('order_date');
            $table->date('delivery_date')->nullable();
            $table->enum('status', ['draft', 'confirmed', 'in_production', 'shipped', 'delivered', 'cancelled'])->default('draft');
            $table->string('currency')->default('USD');
            $table->decimal('exchange_rate', 10, 4)->default(1);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_pct', 5, 2)->default(11)->comment('10% PPN + 1% JS');
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('down_payment_pct', 5, 2)->default(30);
            $table->decimal('down_payment_amount', 15, 2)->default(0);
            $table->string('payment_terms')->nullable();
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
        Schema::dropIfExists('sales_orders');
    }
};