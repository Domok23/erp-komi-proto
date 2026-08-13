<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_subcon_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('po_subcon_id')->constrained('po_subcons')->onDelete('cascade');
            $table->string('description');
            $table->decimal('qty', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_subcon_items');
    }
};
