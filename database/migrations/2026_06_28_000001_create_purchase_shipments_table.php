<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('shipment_number')->unique();
            $table->string('po_type'); // supplier, subcon
            $table->unsignedBigInteger('po_id');
            $table->date('shipment_date');
            $table->string('status')->default('draft'); // draft, shipped, in_transit, customs, arrived, cancelled
            $table->string('shipping_method')->nullable(); // sea, air, land, courier
            $table->string('carrier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('container_number')->nullable();
            $table->string('bl_number')->nullable();
            $table->date('etd')->nullable();
            $table->date('eta')->nullable();
            $table->unsignedInteger('total_packages')->default(0);
            $table->decimal('total_gross_weight_kg', 10, 2)->default(0);
            $table->decimal('total_volume_m3', 10, 4)->default(0);
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'po_type', 'po_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_shipments');
    }
};
