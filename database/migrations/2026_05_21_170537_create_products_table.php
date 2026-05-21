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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('category', [
                'handbag',
                'backpack',
                'duffle',
                'messenger',
                'laptop_bag',
                'trolley',
                'other',
            ]);
            $table->string('unit')->default('pcs');
            $table->decimal('weight_kg', 8, 3)->nullable();
            $table->string('dimensions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'code']);
            $table->index(['company_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};