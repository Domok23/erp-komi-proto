<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('shipment_number')->unique();
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->onDelete('set null');
            $table->date('shipment_date');
            $table->enum('status', ['pending', 'in_transit', 'customs', 'delivered', 'cancelled'])->default('pending');
            $table->enum('shipping_method', ['sea', 'air', 'courier', 'land'])->default('sea');
            $table->string('container_number')->nullable();
            $table->string('bl_number')->nullable();
            $table->string('carrier')->nullable();
            $table->string('port_of_loading')->nullable();
            $table->string('port_of_discharge')->nullable();
            $table->date('etd')->nullable();
            $table->date('eta')->nullable();
            $table->integer('total_packages')->nullable();
            $table->decimal('total_gross_weight_kg', 10, 3)->nullable();
            $table->decimal('total_volume_m3', 10, 3)->nullable();
            $table->decimal('shipping_cost_usd', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
