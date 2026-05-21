<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qc_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('production_order_id')->constrained('production_orders')->onDelete('cascade');
            $table->string('inspection_number')->unique();
            $table->date('inspection_date');
            $table->integer('sample_size');
            $table->integer('passed_qty');
            $table->integer('failed_qty');
            $table->enum('result', ['pass', 'fail', 'conditional'])->default('pass');
            $table->text('notes')->nullable();
            $table->string('inspector')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_inspections');
    }
};
