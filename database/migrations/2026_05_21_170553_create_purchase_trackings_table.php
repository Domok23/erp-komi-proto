<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('po_type'); // supplier, subcon
            $table->unsignedBigInteger('po_id');
            $table->string('tracking_status')->default('pending'); // pending, shipped, customs, delivered, delayed
            $table->date('estimated_arrival')->nullable();
            $table->date('actual_arrival')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'po_type', 'po_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_trackings');
    }
};
