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
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('gr_number')->unique();
            $table->foreignId('purchase_receipt_id')->nullable()->constrained('purchase_receipts');
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->date('receipt_date');
            $table->string('invoice_number')->nullable();
            $table->text('notes')->nullable();
            $table->string('received_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
    }
};