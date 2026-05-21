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
        Schema::create('purchase_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('receipt_number')->unique();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders');
            $table->date('receipt_date');
            $table->enum('status', ['partial', 'completed'])->default('partial');
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
        Schema::dropIfExists('purchase_receipts');
    }
};